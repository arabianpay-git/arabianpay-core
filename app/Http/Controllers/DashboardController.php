<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Product;
use App\Models\State;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // dd('ok');
        return view('admin.dashboard.index');
    }

    public function getStates($country_id)
    {
        return response()->json(State::where('country_id', $country_id)->get());
    }

    public function getCities($state_id)
    {
        return response()->json(City::where('state_id', $state_id)->get());
    }

    public function home()
    {
        // $product = Product::with('attributes.values')->findOrFail(1);

        // foreach ($product->attribute_combinations as $combo) {
        //     echo $combo['attribute'] . ': ' . $combo['value'] . '<br>';
        // }

        // dd($product);

        $cities = City::all();
        return view('welcome', compact('cities'));
    }
}
