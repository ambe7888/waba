<?php
namespace App\Yantrana\Components\Pipeline;

use App\Yantrana\Base\BaseEngine;
use Illuminate\Support\Str;
use App\Yantrana\Components\Pipeline\Models\DealModel;
use App\Yantrana\Components\Pipeline\Models\PipelineStageModel;
use App\Yantrana\Components\Pipeline\Repositories\DealRepository;
use App\Yantrana\Components\Pipeline\Repositories\PipelineStageRepository;
use App\Yantrana\Components\Contact\Models\ContactModel;
use App\Yantrana\Components\User\Repositories\UserRepository;

class PipelineEngine extends BaseEngine
{
    /**
     * @var PipelineStageRepository
     */
    protected $stageRepository;

    /**
     * @var DealRepository
     */
    protected $dealRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * Constructor
     *
     * @param PipelineStageRepository $stageRepository
     * @param DealRepository $dealRepository
     * @param UserRepository $userRepository
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(PipelineStageRepository $stageRepository, DealRepository $dealRepository, UserRepository $userRepository)
    {
        $this->stageRepository = $stageRepository;
        $this->dealRepository = $dealRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Data for the Kanban board: stages (creating the defaults on first
     * visit) plus every open deal, grouped by stage.
     *
     * @return array
     *---------------------------------------------------------------- */
    public function prepareBoardData()
    {
        $vendorId = getVendorId();
        $stages = $this->stageRepository->fetchOrCreateDefaultStages($vendorId);
        $deals = $this->dealRepository->fetchBoardDeals($vendorId);

        return [
            'stages' => $stages,
            'deals' => $deals,
            'vendorMessagingUsers' => $this->userRepository->getVendorMessagingUsers($vendorId),
        ];
    }

    /**
     * Create or update a deal.
     *
     * @param array $inputData
     * @param string|null $dealUid
     * @return array
     *---------------------------------------------------------------- */
    public function processStoreDeal($inputData, $dealUid = null)
    {
        $vendorId = getVendorId();

        $contact = ContactModel::where(['vendors__id' => $vendorId, '_uid' => $inputData['contactUid']])->first();
        if (empty($contact)) {
            return $this->engineFailedResponse([], __tr('Contact introuvable.'));
        }

        $stage = $this->stageRepository->fetchByUid($inputData['stageUid'], $vendorId);
        if (empty($stage)) {
            return $this->engineFailedResponse([], __tr('Étape introuvable.'));
        }

        $assignedUserId = null;
        if (!empty($inputData['assignedUserUid'])) {
            $assignedUser = \App\Yantrana\Components\Auth\Models\AuthModel::where('_uid', $inputData['assignedUserUid'])->first();
            $assignedUserId = $assignedUser->_id ?? null;
        }

        $data = [
            'vendors__id' => $vendorId,
            'contacts__id' => $contact->_id,
            'pipeline_stages__id' => $stage->_id,
            'assigned_users__id' => $assignedUserId,
            'title' => $inputData['title'],
            'value' => $inputData['value'] ?? 0,
            'notes' => $inputData['notes'] ?? null,
            'closed_at' => ($stage->is_won || $stage->is_lost) ? now() : null,
        ];

        if ($dealUid) {
            $deal = $this->dealRepository->fetchByUid($dealUid, $vendorId);
            if (empty($deal)) {
                return $this->engineFailedResponse([], __tr('Opportunité introuvable.'));
            }
            $deal->fill($data);
            $deal->save();
        } else {
            $maxPosition = DealModel::where(['vendors__id' => $vendorId, 'pipeline_stages__id' => $stage->_id])->max('position');
            $data['_uid'] = (string) Str::uuid();
            $data['position'] = ((int) $maxPosition) + 1;
            $deal = DealModel::create($data);
        }

        return $this->engineSuccessResponse(['deal' => $deal->load(['contact', 'assignedUser', 'stage'])], __tr('Opportunité enregistrée.'));
    }

    /**
     * Move a deal to a different stage (drag & drop on the board).
     *
     * @param string $dealUid
     * @param string $stageUid
     * @return array
     *---------------------------------------------------------------- */
    public function processMoveDeal($dealUid, $stageUid)
    {
        $vendorId = getVendorId();
        $deal = $this->dealRepository->fetchByUid($dealUid, $vendorId);
        if (empty($deal)) {
            return $this->engineFailedResponse([], __tr('Opportunité introuvable.'));
        }

        $stage = $this->stageRepository->fetchByUid($stageUid, $vendorId);
        if (empty($stage)) {
            return $this->engineFailedResponse([], __tr('Étape introuvable.'));
        }

        $maxPosition = DealModel::where(['vendors__id' => $vendorId, 'pipeline_stages__id' => $stage->_id])->max('position');
        $deal->pipeline_stages__id = $stage->_id;
        $deal->position = ((int) $maxPosition) + 1;
        $deal->closed_at = ($stage->is_won || $stage->is_lost) ? now() : null;
        $deal->save();

        return $this->engineSuccessResponse([], __tr('Opportunité déplacée.'));
    }

    /**
     * Delete a deal.
     *
     * @param string $dealUid
     * @return array
     *---------------------------------------------------------------- */
    public function processDeleteDeal($dealUid)
    {
        $vendorId = getVendorId();
        $deal = $this->dealRepository->fetchByUid($dealUid, $vendorId);
        if (empty($deal)) {
            return $this->engineFailedResponse([], __tr('Opportunité introuvable.'));
        }
        $deal->delete();
        return $this->engineSuccessResponse([], __tr('Opportunité supprimée.'));
    }

    /**
     * Create a new stage (Kanban column) at the end of the board.
     *
     * @param array $inputData
     * @return array
     *---------------------------------------------------------------- */
    public function processStoreStage($inputData)
    {
        $vendorId = getVendorId();
        $maxPosition = PipelineStageModel::where('vendors__id', $vendorId)->max('position');

        $stage = PipelineStageModel::create([
            '_uid' => (string) Str::uuid(),
            'vendors__id' => $vendorId,
            'title' => $inputData['title'],
            'position' => ((int) $maxPosition) + 1,
            'color' => $inputData['color'] ?? '#64748b',
            'is_won' => false,
            'is_lost' => false,
        ]);

        return $this->engineSuccessResponse(['stage' => $stage], __tr('Étape créée.'));
    }

    /**
     * Rename/recolor a stage.
     *
     * @param string $stageUid
     * @param array $inputData
     * @return array
     *---------------------------------------------------------------- */
    public function processUpdateStage($stageUid, $inputData)
    {
        $vendorId = getVendorId();
        $stage = $this->stageRepository->fetchByUid($stageUid, $vendorId);
        if (empty($stage)) {
            return $this->engineFailedResponse([], __tr('Étape introuvable.'));
        }
        $stage->fill([
            'title' => $inputData['title'] ?? $stage->title,
            'color' => $inputData['color'] ?? $stage->color,
        ]);
        $stage->save();

        return $this->engineSuccessResponse(['stage' => $stage], __tr('Étape mise à jour.'));
    }

    /**
     * Delete a stage. Refuses if it still holds deals, to avoid silently
     * orphaning/cascading them away.
     *
     * @param string $stageUid
     * @return array
     *---------------------------------------------------------------- */
    public function processDeleteStage($stageUid)
    {
        $vendorId = getVendorId();
        $stage = $this->stageRepository->fetchByUid($stageUid, $vendorId);
        if (empty($stage)) {
            return $this->engineFailedResponse([], __tr('Étape introuvable.'));
        }

        if (DealModel::where(['vendors__id' => $vendorId, 'pipeline_stages__id' => $stage->_id])->exists()) {
            return $this->engineFailedResponse([], __tr('Déplacez ou supprimez d\'abord les opportunités de cette étape.'));
        }

        $stage->delete();
        return $this->engineSuccessResponse([], __tr('Étape supprimée.'));
    }
}
