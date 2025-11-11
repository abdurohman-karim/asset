<?php

namespace App\Http\Controllers\Blade;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\Check;
use Illuminate\Http\Request;

class PermissionsController extends Controller
{
    public function index()
    {
        Check::permission('Просмотр разрешений');
        $permissions = Permission::with('roles')->orderByDesc('id')->paginate(25);
        return view('pages.permission.index',compact('permissions'));
    }
}
