<?php

namespace App\Http\Controllers\Blade;

use App\Models\Role;
use App\Models\User;
use App\Services\Check;
use App\Services\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function index()
    {
        Check::permission('Просмотр пользователей');
        $users = User::searchEngine()
            ->orderByDesc('id')
            ->where('id','!=',auth()->id())
            ->with('roles')
            ->paginate();

        return view('pages.users.index',compact('users'));
    }

    public function create()
    {
        Check::permission('Создать пользователя');
        $roles = Role::all();
        return view('pages.users.create',compact('roles'));
    }

    public function store(Request $request)
    {
        Check::permission('Создать пользователя');
        $this->validate($request,[
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = new User();
        $user->fill($request->all());
        $user->phone = Helper::phoneFormatDB($request->get('phone'));
        $user->password = Hash::make($request->password);
        $user->save();

        if (auth()->user()->hasPermission('Установить роль для пользователя') && $request->has('roles'))
        {
            foreach ($request->roles as $role) {
                $user->assignRole($role);
            }
        }
        return redirect()->route('users.index')->with('success',"Создан новый пользователь с именем $user->name!");
    }

    public function edit($id)
    {
        if (auth()->id() != $id)
            Check::permission('Редактировать пользователя');

        $roles = auth()->user()->hasPermission('Установить роль для пользователя') ? Role::all():[];

        $user = User::whereId($id)->with(['roles'])->firstOrFail();
        $user->roles = array_flip($user->roles->map(function ($role){
            return $role->name;
        })->toArray());

        return view('pages.users.edit',compact('user','roles'));
    }


    public function update(Request $request, User $user)
    {
        if ($request->has('phone')) {
            $request->merge(['phone' => Helper::phoneFormatDB($request->get('phone'))]);
        }

        $this->validate($request, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'phone' => ['required', 'min:9', 'string', "unique:users,phone,{$user->id}"],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $data = $request->only(['name', 'email', 'phone']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->fill($data)->save();

        if (auth()->user()->hasPermission("Установить роль для пользователя")) {
            if ($request->has('roles')) {
                $user->roles()->sync($request->roles);
            } else {
                $user->roles()->detach();
            }
        }

        if (auth()->id() === $user->id) {
            return redirect()->back()->with('success', "Ваш профиль успешно обновлен!");
        }

        return redirect()->route('users.index')->with('success', "Пользователь {$user->name} успешно обновлен!");
    }

    public function destroy(User $user)
    {
        Check::permission('Удалить пользователя');
        $user->delete();
        return redirect()->back()->with('success',"Пользователь $user->name успешно удален!");
    }
}
