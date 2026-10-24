<div class="captcha-container">
    <div class="cf-turnstile"
         data-sitekey="{{ $siteKey }}"
         data-response-field-name="captcha"
         data-theme="{{ $theme }}"
         data-size="{{ $size }}"
         @if($action !== '') data-action="{{ $action }}" @endif
    ></div>
    @if($errors->has($errorsName))
        <div class="help-doc">
            <p class="text-danger">{{ $errors->first($errorsName) }}</p>
        </div>
    @endif
</div>
<script src="{{ $script }}" async defer></script>
