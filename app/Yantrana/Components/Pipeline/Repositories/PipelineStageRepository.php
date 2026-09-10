<?php
namespace App\Yantrana\Components\Pipeline\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Pipeline\Models\PipelineStageModel;
use Illuminate\Support\Str;

class PipelineStageRepository extends BaseRepository
{
    /**
     * @var string
     */
    protected $primaryModel = PipelineStageModel::class;

    /**
     * The default set of stages created the first time a vendor opens the
     * pipeline -- a normal sales funnel, with the last two as the fixed
     * "closed" outcomes (won/lost).
     *
     * @var array
     */
    protected $defaultStages = [
        ['title' => 'Prospect', 'color' => '#64748b'],
        ['title' => 'Contacté', 'color' => '#3b82f6'],
        ['title' => 'Proposition envoyée', 'color' => '#f59e0b'],
        ['title' => 'Négociation', 'color' => '#8b5cf6'],
        ['title' => 'Gagné', 'color' => '#10b981', 'is_won' => true],
        ['title' => 'Perdu', 'color' => '#ef4444', 'is_lost' => true],
    ];

    /**
     * Fetch every stage for a vendor, ordered, creating the default set
     * the first time this vendor has none.
     *
     * @param int $vendorId
     * @return \Illuminate\Support\Collection
     *---------------------------------------------------------------- */
    public function fetchOrCreateDefaultStages($vendorId)
    {
        $stages = PipelineStageModel::where('vendors__id', $vendorId)->orderBy('position')->get();
        if ($stages->isNotEmpty()) {
            return $stages;
        }

        foreach ($this->defaultStages as $position => $stage) {
            PipelineStageModel::create([
                '_uid' => (string) Str::uuid(),
                'vendors__id' => $vendorId,
                'title' => $stage['title'],
                'position' => $position,
                'color' => $stage['color'],
                'is_won' => $stage['is_won'] ?? false,
                'is_lost' => $stage['is_lost'] ?? false,
            ]);
        }

        return PipelineStageModel::where('vendors__id', $vendorId)->orderBy('position')->get();
    }

    /**
     * Fetch a stage by uid, scoped to a vendor.
     *
     * @param string $uid
     * @param int $vendorId
     * @return PipelineStageModel|null
     *---------------------------------------------------------------- */
    public function fetchByUid($uid, $vendorId)
    {
        return PipelineStageModel::where(['vendors__id' => $vendorId, '_uid' => $uid])->first();
    }
}
