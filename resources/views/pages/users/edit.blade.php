@extends('layouts.master')
@section('css')
    <link href="{{ URL::asset('/assets/libs/select2/select2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    <div class="row">
        <div class="col-md-6 col-lg-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Редактировать</h4>
                    <x-alert-success/>
                    <form action="{{ route('users.update',$user->id) }}" method="post">
                        @method('put')
                        @csrf
                        <div class="row">
                            <div class="mb-3 col-lg-6 col-md-6 col-sm-12">
                                <label for="useremail" class="form-label">Электронная почта</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="useremail"
                                       value="{{ old('email',$user->email) }}" name="email" placeholder="Enter email" autofocus
                                       required>
                                @error('email')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="mb-3 col-lg-6 col-md-6 col-sm-12">
                                <label for="username" class="form-label">Имя</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name',$user->name) }}" id="username" name="name" autofocus required
                                       placeholder="Enter name">
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="mb-3 col-lg-6 col-md-6 col-sm-12">
                                <label for="v" class="form-label">Телефон</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone',$user->phone) }}" id="phone" name="phone" autofocus required
                                       placeholder="+998.....">
                                @error('phone')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                     </span>
                                @enderror
                            </div>
                            <div class="mb-4 col-lg-6 col-md-6 col-sm-12">
                                <label for="userpassword" class="form-label">Пароль</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       id="userpassword" name="password"
                                       placeholder="Enter password" autofocus>
                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="mb-3 col-lg-6 col-md-6 col-sm-12">
                                <label class="form-label">Выбрать роль</label>
                                <select class="select2 form-control select2-multiple" multiple="multiple" name="roles[]"
                                        @cannot("Установить роль для пользователя") disabled @endif
                                        data-placeholder="Tanlash ..." disabled>
                                    @foreach($roles as $role)
                                        <option @isset($user->roles[$role->name]) selected @endisset value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 col-lg-6 col-md-6 col-sm-12">
                                <label class="form-label">Логин</label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror"
                                       value="{{ old('username',$user->username) }}" id="username" name="username" autofocus required
                                       placeholder="Enter username" disabled>
                                @error('username')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="text-lg-end text-sm-center">
                                <div>
                                    <button type="submit" class="btn btn-success w-md waves-effect waves-light"><i
                                            class="fas fa-save"></i> Сохранить
                                    </button>
                                    <a href="{{ url()->previous() }}" class="btn btn-secondary w-md">Отмена</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ URL::asset('/assets/libs/select2/select2.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/pages/form-advanced.init.js') }}"></script>
@endsection
