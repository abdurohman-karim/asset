<?php

namespace App\Http\Controllers\Blade;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Check;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class RolesController extends Controller
{
    public function index()
    {
        Check::permission('Посмотреть роли');
        $roles = Role::with('permissions')->paginate(25);
        return view('pages.role.index', compact('roles'));
    }


    public function create()
    {
        Check::permission('Создать новую роль');
        $permissions = Permission::all();
        return view('pages.role.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        Check::permission('Создать новую роль');
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails())
            return redirect()->back()->withErrors($validator);
        if (Role::where('name', $request->name)->exists())
            return redirect()->back()->with('error', 'Роль с таким именем уже существует');

        $role = Role::create([
            'name' => $request->name,
            'partner_id' => auth()->user()->partner_id
        ]);

        if ($request->has('permissions'))
            foreach ($request->permissions as $item) {
                $role->givePermissionTo($item);
            }


        return redirect()->route('roles.index')->with('success', 'Роль успешно создана');
    }

    public function edit(Role $role)
    {
        Check::permission('Редактировать роли');
        $available = $role->permissions->pluck('name','id')->toArray();
        $permissions = Permission::all();
        return view('pages.role.edit',compact('role','permissions','available'));
    }


    public function update(Request $request, Role $role)
    {
        Check::permission('Редактировать роли');
        $request->validate([
            'name' => "required"
        ]);

        $role->name = $request->name;

        if ($role->isDirty()) $role->save();
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }
        else
            $role->permissions()->detach();

        return redirect()->route('roles.index')->with('success', 'Роль успешно обновлена');
    }


    public function destroy(Role $role)
    {
        Check::permission('Удаление роли');
        if ($role->name === 'Super Admin')
            return redirect()->back()->with('error', 'Роль администратора не может быть удалена');
        $role->permissions()->detach();
        $role->delete();
        return redirect()->back()->with('success', 'Роль успешно удалена');
    }
}
