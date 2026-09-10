<?php
namespace App\Yantrana\Components\Pipeline\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequest;
use App\Yantrana\Components\Pipeline\PipelineEngine;

class PipelineController extends BaseController
{
    /**
     * @var PipelineEngine
     */
    protected $pipelineEngine;

    /**
     * Constructor
     *
     * @param PipelineEngine $pipelineEngine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(PipelineEngine $pipelineEngine)
    {
        $this->pipelineEngine = $pipelineEngine;
    }

    /**
     * Show the Kanban pipeline board.
     *
     * @return view
     *---------------------------------------------------------------- */
    public function showBoardView()
    {
        validateVendorAccess('manage_pipeline');
        $boardData = $this->pipelineEngine->prepareBoardData();
        return $this->loadView('pipeline.board', $boardData);
    }

    /**
     * AJAX contact search for the "new deal" Selectize picker.
     *
     * @param BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function searchContacts(BaseRequest $request)
    {
        if (!hasVendorAccess('manage_pipeline')) {
            return response()->json([]);
        }

        $vendorId = getVendorId();
        $search = trim($request->get('q', ''));

        $query = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
            ->select('_uid', 'first_name', 'last_name', 'wa_id');

        if ($search !== '') {
            $escapedSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('first_name', 'LIKE', "%{$escapedSearch}%")
                  ->orWhere('last_name', 'LIKE', "%{$escapedSearch}%")
                  ->orWhere('wa_id', 'LIKE', "%{$escapedSearch}%");
            });
        }

        $limit = $search === '' ? 200 : 100;
        $contacts = $query->orderBy('first_name')->limit($limit)->get();

        return response()->json($contacts->map(function ($c) {
            return [
                'value' => $c->_uid,
                'text' => trim($c->first_name . ' ' . $c->last_name) . ' (+' . $c->wa_id . ')',
            ];
        }));
    }

    /**
     * Create or update a deal.
     *
     * @param BaseRequest $request
     * @param string|null $dealUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processStoreDeal(BaseRequest $request, $dealUid = null)
    {
        if (!hasVendorAccess('manage_pipeline', 'add_edit_deals')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $request->validate([
            'contactUid' => 'required|uuid',
            'stageUid' => 'required|uuid',
            'title' => 'required|string|max:150',
            'value' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'assignedUserUid' => 'nullable|string',
        ]);

        $processReaction = $this->pipelineEngine->processStoreDeal($request->all(), $dealUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Move a deal to a different stage (drag & drop).
     *
     * @param BaseRequest $request
     * @param string $dealUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processMoveDeal(BaseRequest $request, $dealUid)
    {
        if (!hasVendorAccess('manage_pipeline', 'add_edit_deals')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $request->validate([
            'stageUid' => 'required|uuid',
        ]);

        $processReaction = $this->pipelineEngine->processMoveDeal($dealUid, $request->stageUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Delete a deal.
     *
     * @param string $dealUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processDeleteDeal($dealUid)
    {
        if (!hasVendorAccess('manage_pipeline', 'delete_deals')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $processReaction = $this->pipelineEngine->processDeleteDeal($dealUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Create a new stage (Kanban column).
     *
     * @param BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function processStoreStage(BaseRequest $request)
    {
        if (!hasVendorAccess('manage_pipeline', 'manage_pipeline_stages')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $request->validate([
            'title' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        $processReaction = $this->pipelineEngine->processStoreStage($request->all());
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Update a stage's title/color.
     *
     * @param BaseRequest $request
     * @param string $stageUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processUpdateStage(BaseRequest $request, $stageUid)
    {
        if (!hasVendorAccess('manage_pipeline', 'manage_pipeline_stages')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $request->validate([
            'title' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        $processReaction = $this->pipelineEngine->processUpdateStage($stageUid, $request->all());
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Delete a stage (refused while it still holds deals).
     *
     * @param string $stageUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processDeleteStage($stageUid)
    {
        if (!hasVendorAccess('manage_pipeline', 'manage_pipeline_stages')) {
            return $this->processResponse(3, [3 => __tr('Action non autorisée.')], ['message' => __tr('Action non autorisée.')]);
        }

        $processReaction = $this->pipelineEngine->processDeleteStage($stageUid);
        return $this->processResponse($processReaction, [], [], true);
    }
}
