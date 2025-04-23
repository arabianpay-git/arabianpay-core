<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstalmentPlanTranslation extends Model
{
    protected $fillable = ['instalment_plan_id', 'locale', 'name', 'description'];

    /**
     * Get the instalment plan that owns the translation.
     */
    public function instalmentPlan()
    {
        return $this->belongsTo(InstalmentPlan::class);
    }
}
