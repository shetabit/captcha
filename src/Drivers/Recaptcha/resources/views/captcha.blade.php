<div class="captcha-container">
    <input type="hidden" name="captcha" id="{{ $id }}">
    @if($errors->has($errorsName))
        <div class="help-doc">
            <p class="text-danger">{{ $errors->first($errorsName) }}</p>
        </div>
    @endif
</div>
<script src="{{ $script }}?render={{ urlencode($siteKey) }}"></script>
<script>
    (function (input, siteKey, action) {
        if (!input.form) {
            return;
        }

        input.form.addEventListener('submit', function (event) {
            if (input.value !== '' || typeof grecaptcha === 'undefined') {
                return;
            }

            event.preventDefault();

            grecaptcha.ready(function () {
                grecaptcha.execute(siteKey, {action: action}).then(function (token) {
                    input.value = token;
                    input.form.requestSubmit(event.submitter);
                });
            });
        });
    })(document.getElementById(@json($id)), @json($siteKey), @json($action));
</script>
