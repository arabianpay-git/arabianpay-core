<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkProductUploadRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ProductBulkUploadController extends Controller
{

    /**
     * Show the bulk upload form.
     */
    public function bulkUploadForm()
    {
        return view('admin.products.bulk-upload');
    }

    /**
     * Handle the uploaded CSV/TXT/XLSX file and create products in bulk.
     */
    public function bulkUpload(BulkProductUploadRequest $request)
    {
        $uploadedFile = $request->file('file');
        $filePath     = $uploadedFile->getRealPath();
        $extension    = strtolower($uploadedFile->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'])) {
            $allLines = file($filePath);
            $rows     = array_map('str_getcsv', $allLines);
        } elseif ($extension === 'xlsx') {
            try {
                $sheets = Excel::toArray(null, $uploadedFile);
            } catch (\Throwable $e) {
                return back()->with('error', __('Failed to read XLSX file: :msg', ['msg' => $e->getMessage()]));
            }
            $rows = $sheets[0];
        } else {
            return back()->with('error', __('Unsupported file type. Please upload CSV or XLSX.'));
        }

        if (empty($rows) || count($rows) < 2) {
            return back()->with('error', __('The uploaded file is empty or missing data rows.'));
        }

        $rawHeader = array_shift($rows);
        $header = array_map(function ($col) {
            return strtolower(trim(preg_replace('/\x{FEFF}/u', '', $col)));
        }, $rawHeader);

        $parsedData = [];
        foreach ($rows as $row) {
            $rowData = [];
            foreach ($header as $i => $key) {
                $rowData[$key] = $row[$i] ?? null;
            }
            $parsedData[] = $rowData;
        }

        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $merchants = User::where('user_type', 'merchant')->select('id', 'business_name')->get();
        return view('admin.products.bulk-upload', compact(
            'parsedData',
            'header',
            'categories',
            'brands',
            'merchants'
        ));
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'products' => ['required', 'array'],
            'products.*.name' => ['required', 'string', 'max:255'],
            'products.*.unit_price' => ['required', 'numeric', 'min:0'],
            'products.*.description' => ['nullable', 'string'],
            'products.*.unit' => ['required', 'string', 'max:50'],
            'products.*.stock' => ['nullable', 'integer', 'min:0'],
            'products.*.category_id' => ['required', 'exists:categories,id'],
            'products.*.brand_id' => ['nullable', 'exists:brands,id'],
            'products.*.thumbnail' => ['nullable', 'string'],
        ]);

        foreach ($validated['products'] as $product) {
            Product::create([
                'name' => $product['name'],
                'unit_price' => $product['unit_price'],
                'description' => $product['description'] ?? null,
                'unit' => $product['unit'],
                'current_stock' => $product['stock'] ?? 0,
                'category_id' => $product['category_id'],
                'brand_id' => $product['brand_id'] ?? null,
                'thumbnail' => $product['thumbnail'] ?? null,
                'added_by'       => Auth::user()->user_type ?? 'admin',
                'user_id'        => $request->user_id ?? Auth::id(),
                'published'      => 'published',
                'approved'       => 'approved',
            ]);
        }

        return redirect()->back()->with('success', 'Products uploaded successfully.');
    }
}
