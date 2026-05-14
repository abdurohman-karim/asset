@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-md-8 col-lg-8 offset-lg-2 offset-md-2 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Редактировать валюту</h4>
                    <x-alert-success />

                    <form action="{{ route('currencies.update', $currency) }}" method="post">
                        @include('pages.currencies._form', [
                            'currency' => $currency,
                            'isEdit' => true,
                            'submitLabel' => 'Сохранить',
                        ])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
