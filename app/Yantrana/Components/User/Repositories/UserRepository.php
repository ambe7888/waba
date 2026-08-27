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


/**
 * UserRepository.php - Repository file
 *
 * This file is part of the User component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\User\Repositories;

use Illuminate\Support\Facades\Auth;
use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Auth\Models\AuthModel;
use App\Yantrana\Components\Auth\Repositories\AuthRepository;
use App\Yantrana\Components\User\Interfaces\UserRepositoryInterface;
use App\Yantrana\Components\Vendor\Models\VendorUserModel;

class UserRepository extends AuthRepository implements UserRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = AuthModel::class;

    public function updateLoggedInUserProfile($updateData)
    {
        
        $user = Auth::user();
        $dataToUpdate = [
            'first_name' => $updateData['first_name'],
            'last_name' => $updateData['last_name'],
            'mobile_number'=> $updateData['mobile_number'],
        ];
        if ($user->email !== $updateData['email']) {
            $dataToUpdate['email'] = $updateData['email'];
            $dataToUpdate['email_verified_at'] = null;
        }

        return $this->updateIt($user, $dataToUpdate);
    }

    public function updateUserData($userData, $requireColumnsForUser)
    {
        // Check if page updated then return positive response
        if ($userData->modelUpdate($requireColumnsForUser)) {
            return true;
        }

        return false;
    }

    /**
      * Fetch user datatable source
      *
      * @return  mixed
      *---------------------------------------------------------------- */
    public function fetchUserDataTableSource()
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'first_name',
                'last_name',
                'username',
                'email',
            ]
        ];
        // get Model result for dataTables
        return $this->primaryModel::select([
            'users.*',
            'vendor_users.users__id',
            'vendor_users.vendors__id',
        ])->leftJoin('vendor_users', 'users._id', '=', 'vendor_users.users__id')
                    ->where('vendor_users.vendors__id', getVendorId())
                    ->with('role')->dataTables($dataTableConfig)
                    ->toArray();
    }

    /**
      * Delete $user record and return response
      *
      * @param  object $inputData
      *
      * @return  mixed
      *---------------------------------------------------------------- */

    public function deleteUser($user)
    {
        // Check if $user deleted
        if ($user->deleteIt()) {
            // if deleted
            return true;
        }
        // if failed to delete
        return false;
    }

    /**
     * Get vendor users count
     *
     * @param int $vendorId
     * @return number
     */
    function countVendorUsers($vendorId) {
        return VendorUserModel::where('vendors__id', $vendorId)->count();
    }
    /**
     * Get vendor active users count
     *
     * @param int $vendorId
     * @return number
     */
    function countVendorsActiveUsers($vendorId) {
        return $this->primaryModel::leftJoin('vendor_users', 'users._id', '=', 'vendor_users.users__id')
        ->where('users.status','=', 1)
        ->where('vendor_users.vendors__id', $vendorId)->count();
    }
    /**
     * check if it is a vendor user
     *
     * @param int $userId
     * @return number
     */
    function isVendorUser($userId, $vendorId = null) {
        $vendorId = $vendorId ?: getVendorId();
        return VendorUserModel::where([
            'vendors__id' => $vendorId,
            'users__id' => $userId,
        ])->count();
    }
    /**
     * Get vendor users who have the Messaging Permission / belong to vendor
     *
     * @param int $vendorId
     * @return Eloquent Collection
     */
    function getVendorMessagingUsers($vendorId) {
        // Fetch primary vendor owner user(s)
        $vendorOwnerUsers = $this->fetchItAll([
            'vendors__id' => $vendorId,
            'status' => 1
        ]);

        // Fetch team member user IDs linked via vendor_users table
        $vendorMessagingUserIds = VendorUserModel::where([
            'vendors__id' => $vendorId,
        ])->pluck('users__id')->toArray();

        if (!empty($vendorMessagingUserIds)) {
            $teamMemberUsers = $this->fetchItAll($vendorMessagingUserIds, null, '_id', [
                'where' => [
                    'status' => 1
                ]
            ]);
            if ($teamMemberUsers && $teamMemberUsers->count()) {
                $vendorOwnerUsers = $vendorOwnerUsers->merge($teamMemberUsers);
            }
        }

        return $vendorOwnerUsers->unique('_id')->values();
    }

    function fetchTeamMembers() 
    {
        $vendorId = getVendorId();
        return $this->getVendorMessagingUsers($vendorId);
    }

    /**
     * Fetch agents/team members list for API (mobile app) including vendor owner
     *
     * @param int $vendorId
     * @return \Illuminate\Support\Collection
     */
    public function fetchAgentsList($vendorId)
    {
        // 1. Fetch team members linked via vendor_users
        $teamMembers = $this->primaryModel::select([
            'users._id',
            'users._uid',
            'users.first_name',
            'users.last_name',
            'users.username',
            'users.email',
            'users.mobile_number',
            'users.status',
            'users.user_roles__id',
            'users.created_at',
        ])
            ->join('vendor_users', 'users._id', '=', 'vendor_users.users__id')
            ->where('vendor_users.vendors__id', $vendorId)
            ->where('users.status', 1)
            ->with('role')
            ->get();

        // 2. Fetch primary vendor owner user(s)
        $vendorOwners = $this->primaryModel::select([
            'users._id',
            'users._uid',
            'users.first_name',
            'users.last_name',
            'users.username',
            'users.email',
            'users.mobile_number',
            'users.status',
            'users.user_roles__id',
            'users.created_at',
        ])
            ->where('users.vendors__id', $vendorId)
            ->where('users.status', 1)
            ->with('role')
            ->get();

        return $vendorOwners->merge($teamMembers)->unique('_id')->sortBy('first_name')->values();
    }

    public function getRandomTemMember($vendorId)
    {
        // only vendor users having messaging permission
        return VendorUserModel::where([
            'vendor_users.vendors__id' => $vendorId,
            'vendor_users.__data->permissions->messaging' => 'allow',
            'users.status' => 1, // Active
            'user_roles__id' => 3 // Only Team Member
        ])
        ->select('users._id', 'users.status', 'vendor_users.users__id', 'vendor_users.vendors__id', 'vendor_users.__data')
        ->leftJoin('users', 'vendor_users.users__id', '=', 'users._id')
        ->inRandomOrder()
        ->first();
    }
}
