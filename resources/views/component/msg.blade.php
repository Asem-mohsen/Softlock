@if(session('error'))
    <div id='errorMessages' class='ErrorMessages position-relative'>
        <div class='alert alert-danger text-center position-absolute'>
            <i class='fa-regular fa-circle-xmark'></i>
            <p> {{ session('error') }}</p>
        </div>
    </div>
@endif
