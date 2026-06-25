<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditTrailService
{
    protected $request;

    protected $user;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->user = Auth::user();
    }

    /**
     * Log an audit trail event
     */
    public function log(array $data): AuditTrail
    {
        // Default values
        $defaults = [
            'timestamp' => now(),
            'environment' => config('app.env'),
            'request_id' => $this->request->header('X-Request-ID') ?? (string) Str::uuid(),
            'correlation_id' => $this->request->header('X-Correlation-ID') ?? (string) Str::uuid(),
            'ip_address' => $this->request->ip() ?? '127.0.0.1',
        ];

        // Set actor information if user is authenticated
        if ($this->user) {
            $defaults['actor_type'] = 'user';
            $defaults['actor_id'] = $this->user->id;
            $defaults['actor_email'] = $this->user->email;
            $defaults['actor_role'] = $this->user->roles->first()?->name ?? 'user';
        } else {
            $defaults['actor_type'] = 'system';
            $defaults['actor_id'] = 'system';
        }

        // Merge defaults with provided data
        $auditData = array_merge($defaults, $data);

        // Convert arrays to JSON strings if they're not already strings
        $jsonFields = ['pii_fields_involved', 'before_state', 'after_state', 'properties'];
        foreach ($jsonFields as $field) {
            if (isset($auditData[$field]) && is_array($auditData[$field])) {
                $auditData[$field] = json_encode($auditData[$field]);
            }
        }

        // Create audit trail record
        return AuditTrail::create($auditData);
    }

    /**
     * Log a CRUD operation
     *
     * @param  mixed  $entityId
     * @param  mixed  $beforeState
     * @param  mixed  $afterState
     */
    public function logCrudOperation(
        string $eventType,
        string $entityType,
        $entityId,
        string $actionSummary,
        $beforeState = null,
        $afterState = null,
        array $additionalData = []
    ): AuditTrail {
        $data = array_merge([
            'event_category' => 'crud_operations',
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action_summary' => $actionSummary,
            'before_state' => $beforeState,
            'after_state' => $afterState,
        ], $additionalData);

        return $this->log($data);
    }

    /**
     * Log a view operation (no entity ID needed)
     */
    public function logViewOperation(
        string $eventType,
        string $entityType,
        string $actionSummary,
        array $properties = []
    ): AuditTrail {
        return $this->log([
            'event_category' => 'view_operations',
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'action_summary' => $actionSummary,
            'properties' => $properties,
        ]);
    }

    /**
     * Log a model creation event
     */
    public function logCreated(Model $model, string $actionSummary = '', array $additionalData = []): AuditTrail
    {
        $modelName = class_basename($model);
        $summary = $actionSummary ?: "Created new {$modelName}";

        return $this->logCrudOperation(
            'create',
            $modelName,
            $model->id,
            $summary,
            null,
            $model->toArray(),
            $additionalData
        );
    }

    /**
     * Log a model update event
     */
    public function logUpdated(Model $model, array $oldData, string $actionSummary = '', array $additionalData = []): AuditTrail
    {
        $modelName = class_basename($model);
        $summary = $actionSummary ?: "Updated {$modelName}";

        return $this->logCrudOperation(
            'update',
            $modelName,
            $model->id,
            $summary,
            $oldData,
            $model->toArray(),
            $additionalData
        );
    }

    /**
     * Log a model deletion event
     */
    public function logDeleted(Model $model, string $actionSummary = '', array $additionalData = []): AuditTrail
    {
        $modelName = class_basename($model);
        $summary = $actionSummary ?: "Deleted {$modelName}";

        return $this->logCrudOperation(
            'delete',
            $modelName,
            $model->id,
            $summary,
            $model->toArray(),
            null,
            $additionalData
        );
    }

    /**
     * Log a search event
     */
    public function logSearch(string $entityType, string $searchQuery, int $resultsCount, array $additionalData = []): AuditTrail
    {
        return $this->log(array_merge([
            'event_category' => 'search_operations',
            'event_type' => 'search',
            'entity_type' => $entityType,
            'action_summary' => "Searched {$entityType} with query: '{$searchQuery}' - Found {$resultsCount} results",
            'properties' => [
                'search_query' => $searchQuery,
                'results_count' => $resultsCount,
            ],
        ], $additionalData));
    }

    /**
     * Set justification for an action (GDPR requirement)
     */
    public function withJustification(string $justification, string $pdplCategory = 'legitimate_interest', array $piiFields = []): array
    {
        return [
            'justification' => $justification,
            'pdpl_category' => $pdplCategory,
            'pii_fields_involved' => json_encode($piiFields),
        ];
    }
}
