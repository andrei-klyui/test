<div class="modal fade" id="add-modal" tabindex="-1" role="dialog" aria-labelledby="add-title" style="display: none;"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="add-title">@lang('i.create') @choice('i.city', 3)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form method="POST" action="{{ route('city.store') }}">
                <div class="modal-body">
                    <div class="card-body">
                        @csrf
                        @foreach($langs as $lang)
                            <div class="form-group">
                                <label for="name_{{ $lang }}">{{ __('i.name') }} {{ $lang }}</label>
                                <input type="text"
                                       class="form-control{{ $errors->has('name_'. $lang) ? ' is-invalid' : '' }}"
                                       value="{{ old('name_'. $lang) }}"
                                       name="name_{{ $lang }}"
                                       id="name_{{ $lang }}" placeholder="{{ __('i.name') }} {{ $lang }}"
                                        {{ $lang === 'ru' ? 'required' : '' }}>
                                @if ($errors->has('name_'. $lang))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('name_'. $lang) }}</strong>
                                    </span>
                                @endif
                            </div>
                        @endforeach
                        <div class="form-group">
                            <label for="country_id">@choice('i.country', 1)</label>
                            <select class="form-control form-control-lg" name="country_id" required>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" {{ old('country_id') === $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('country_id'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('country_id') }}</strong>
                                </span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="emailsTextarea">{{ __('i.additional_emails')}}</label>
                            <textarea class="form-control {{ $errors->has('additional_emails') ? ' is-invalid' : '' }}" name="additional_emails" id="emailsTextarea" rows="5" >{{ old('additional_emails')}}</textarea>
                            @if ($errors->has('additional_emails'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('additional_emails') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">{{ __('i.create') }}</button>
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('i.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
