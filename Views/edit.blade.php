@extends('layouts.app')

@section('content')

    <div class="page-header">
        <h3 class="page-title"> @choice('i.city', 1) </h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <a href="{{ route('city.index') }}">
                    <button type="button" class="btn btn-inverse-dark btn-icon btn-rounded">
                        <i class="mdi mdi-arrow-left-bold"></i>
                    </button>
                </a>
                @if($data->cities_count == 0)
                    <button type="button" class="btn btn-inverse-danger btn-rounded btn-icon verifyRedir"
                            href="{{ route('city.destroy', [$data->id]) }}"
                            data-href="{{ route('city.index') }}"
                            data-title="{{ __('i.delete_confirm') }}"
                            data-description="{{ __('i.delete_alert') }}">
                        <i class="mdi mdi-delete"></i>
                    </button>
                @endif
            </ol>
        </nav>
    </div>

    <div class="row grid-margin">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form class="cmxform" method="POST" action="{{ route('city.update', [$data->id]) }}"
                          novalidate="novalidate">
                        @csrf
                        <input name="_method" type="hidden" value="PUT">
                        {{ Form::hidden('country_id', $data->country_id) }}
                        @foreach($data->langs as $lang)
                            <div class="form-group row">
                                <div class="col-lg-3">
                                    <label for="name_{{ $lang }}">{{ __('i.name') }} {{ $lang }}</label>
                                </div>
                                <div class="col-lg-8">
                                    <input type="text"
                                           class="form-control{{ $errors->has('name_'. $lang) ? ' is-invalid' : '' }}"
                                           value="{{ old('name_'. $lang) ?: $data->{'name_'. $lang} }}"
                                           name="name_{{ $lang }}"
                                           id="name_{{ $lang }}" placeholder="{{ __('i.name') }}  {{ $lang }}"
                                            {{ $lang === 'ru' ? 'required' : '' }}>
                                    @if ($errors->has('name_'. $lang))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('name_'. $lang) }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <div class="form-group">
                            <label for="emailsTextarea">{{ __('i.additional_emails')}}</label>
                            <textarea class="form-control {{ $errors->has('additional_emails') ? ' is-invalid' : '' }}" name="additional_emails" id="emailsTextarea" rows="5" >{{$data->additional_emails ?? old('additional_emails')}}</textarea>
                            @if ($errors->has('additional_emails'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('additional_emails') }}</strong>
                                </span>
                            @endif
                        </div>
                        <button class="btn btn-primary" type="submit"> {{ __('i.save') }} </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
