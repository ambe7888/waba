<?php
/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2024 - 2026 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2024 - 2026 livelyworks
 * @website     https://livelyworks.net
 */

namespace App\Yantrana\Components\WhatsAppService\Controllers;

use App\Yantrana\Base\BaseController;
use Illuminate\Http\Request;
use App\Yantrana\Components\WhatsAppService\Models\CannedReplyModel;
use Illuminate\Support\Str;

class CannedReplyController extends BaseController
{
    /**
     * Get Canned Replies list
     */
    public function getCannedReplies(Request $request)
    {
        $vendorId = getVendorId();
        $cannedReplies = CannedReplyModel::where('vendors__id', $vendorId)->latest()->get();

        return $this->processResponse(1, [], [
            'canned_replies' => $cannedReplies
        ]);
    }

    /**
     * Save Canned Reply (Add or Update)
     */
    public function saveCannedReply(Request $request)
    {
        $vendorId = getVendorId();
        
        $request->validate([
            'uid' => 'nullable|string',
            'shortcut' => 'required|string|max:50',
            'message' => 'required|string',
        ]);

        $shortcut = trim($request->shortcut);
        if (!Str::startsWith($shortcut, '/')) {
            $shortcut = '/' . $shortcut;
        }

        if ($request->uid) {
            $cannedReply = CannedReplyModel::where([
                'vendors__id' => $vendorId,
                '_uid' => $request->uid
            ])->first();

            if (empty($cannedReply)) {
                return $this->processResponse(2, [], [
                    'message' => __tr('Canned reply not found.')
                ]);
            }

            $cannedReply->update([
                'shortcut' => $shortcut,
                'message' => $request->message
            ]);

            return $this->processResponse(1, [], [
                'message' => __tr('Canned reply updated successfully.'),
                'canned_reply' => $cannedReply
            ]);
        } else {
            // Check limit or existing shortcut
            $exists = CannedReplyModel::where([
                'vendors__id' => $vendorId,
                'shortcut' => $shortcut
            ])->exists();

            if ($exists) {
                return $this->processResponse(3, [], [
                    'message' => __tr('A canned reply with this shortcut already exists.')
                ]);
            }

            $cannedReply = CannedReplyModel::create([
                '_uid' => generateUID(),
                'vendors__id' => $vendorId,
                'shortcut' => $shortcut,
                'message' => $request->message
            ]);

            return $this->processResponse(1, [], [
                'message' => __tr('Canned reply created successfully.'),
                'canned_reply' => $cannedReply
            ]);
        }
    }

    /**
     * Delete Canned Reply
     */
    public function deleteCannedReply($uid)
    {
        $vendorId = getVendorId();
        
        $cannedReply = CannedReplyModel::where([
            'vendors__id' => $vendorId,
            '_uid' => $uid
        ])->first();

        if (empty($cannedReply)) {
            return $this->processResponse(2, [], [
                'message' => __tr('Canned reply not found.')
            ]);
        }

        $cannedReply->delete();

        return $this->processResponse(1, [], [
            'message' => __tr('Canned reply deleted successfully.')
        ]);
    }
}
