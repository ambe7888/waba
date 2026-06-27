<?php

namespace App\Yantrana\Components\InfoMaterial\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\InfoMaterial\Models\InfoMaterialModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InfoMaterialController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (hasCentralAccess()) {
            $materials = InfoMaterialModel::orderBy('created_at', 'desc')->paginate(20);
        } else {
            $vendorId = getVendorId();
            $materials = InfoMaterialModel::whereNull('vendors__id')
                            ->orWhere('vendors__id', $vendorId)
                            ->orderBy('created_at', 'desc')
                            ->paginate(20);
        }

        return view('info_material.index', compact('materials'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        abortIf(!hasCentralAccess());
        return view('info_material.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        abortIf(!hasCentralAccess());

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        $path = null;
        $originalName = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . Str::slug($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('info_materials', $filename, 'public');
            $originalName = $file->getClientOriginalName();
        }

        InfoMaterialModel::create([
            'status' => 1,
            'title' => $request->title,
            'description' => $request->description ?? '',
            'type' => 1, // 1 for file
            '__data' => [
                'file_path' => $path,
                'file_name' => $originalName
            ]
        ]);

        return redirect()->route('info_material.index')->with('success', __tr('Material uploaded successfully.'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $uid
     * @return \Illuminate\View\View
     */
    public function edit($uid)
    {
        abortIf(!hasCentralAccess());
        $material = InfoMaterialModel::where('_uid', $uid)->firstOrFail();
        return view('info_material.edit', compact('material'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $uid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $uid)
    {
        abortIf(!hasCentralAccess());

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        $material = InfoMaterialModel::where('_uid', $uid)->firstOrFail();
        $data = $material->__data;

        if ($request->hasFile('file')) {
            // Delete old file if exists
            if (isset($data['file_path'])) {
                Storage::disk('public')->delete($data['file_path']);
            }
            
            $file = $request->file('file');
            $filename = time() . '_' . Str::slug($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('info_materials', $filename, 'public');
            
            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
        }

        $material->update([
            'title' => $request->title,
            'description' => $request->description ?? '',
            '__data' => $data
        ]);

        return redirect()->route('info_material.index')->with('success', __tr('Material updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $uid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($uid)
    {
        abortIf(!hasCentralAccess());

        $material = InfoMaterialModel::where('_uid', $uid)->firstOrFail();

        $data = $material->__data;
        if (isset($data['file_path'])) {
            Storage::disk('public')->delete($data['file_path']);
        }

        $material->delete();

        return redirect()->route('info_material.index')->with('success', __tr('Material deleted successfully.'));
    }

    /**
     * Download the specified resource.
     *
     * @param  string  $uid
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($uid)
    {
        $material = InfoMaterialModel::where('_uid', $uid)->firstOrFail();
        
        if (!hasCentralAccess()) {
            $vendorId = getVendorId();
            if ($material->vendors__id && $material->vendors__id != $vendorId) {
                abort(403);
            }
        }

        $data = $material->__data;
        $path = storage_path('app/public/' . $data['file_path']);

        if (!file_exists($path)) {
            abort(404, __tr('File not found.'));
        }

        return response()->download($path, $data['file_name']);
    }

    /**
     * API: Display listing of the resources for mobile application.
     *
     * @return json
     */
    public function apiList()
    {
        $vendorId = getVendorId();
        $materials = InfoMaterialModel::whereNull('vendors__id')
                        ->orWhere('vendors__id', $vendorId)
                        ->orderBy('created_at', 'desc')
                        ->get();

        $formatted = $materials->map(function ($material) {
            return [
                'uid' => $material->_uid,
                'title' => $material->title,
                'description' => $material->description,
                'file_name' => $material->__data['file_name'] ?? 'file',
                'download_url' => route('app_api.vendor.info_materials.download', ['uid' => $material->_uid]),
            ];
        });

        return __apiResponse([
            'materials' => $formatted
        ]);
    }

    /**
     * API: Download the resource from mobile application.
     *
     * @param string $uid
     * @return response
     */
    public function apiDownload($uid)
    {
        $material = InfoMaterialModel::where('_uid', $uid)->firstOrFail();
        
        $vendorId = getVendorId();
        if ($material->vendors__id && $material->vendors__id != $vendorId) {
            return __apiResponse([
                'message' => __tr('Unauthorized access.')
            ], 3);
        }

        $data = $material->__data;
        $path = storage_path('app/public/' . $data['file_path']);

        if (!file_exists($path)) {
            abort(404, __tr('File not found.'));
        }

        return response()->download($path, $data['file_name']);
    }
}
