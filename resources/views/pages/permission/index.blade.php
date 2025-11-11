@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-12 col-lg-6">
                            <h4 class="card-title">Разрешение</h4>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap table-check">
                            <thead class="table-light">
                            <tr>
                                <th class="align-middle">Имя</th>
                                <th class="align-middle">Роли</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($permissions as $permission)
                                <tr>
                                    <td>{{ $permission->name }}</td>
                                    <td>
                                        @foreach($permission->roles as $role)
                                            <span class="badge badge-soft-success font-size-12">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $permissions->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
