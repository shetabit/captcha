<div class="captcha-container">
    <a title="click for new captcha" onclick="document.getElementById('captcha').src='{{ route($routeName) }}?rnd='+Math.random();" href="javascript:void(0)">
        <img id="captcha" src="{{ route($routeName) }}" alt="captcha">
    </a>
    <div class="form-group {{ $errors->has($errorsName) ? 'has-error' : '' }}">
        <input name="captcha" class="from-control" type="text" required>
        @if($errors->has($errorsName))
            <div class="help-doc">
                <p class="text-danger">{{ $errors->first($errorsName) }}</p>
            </div>
        @endif
    </div>
</div>
