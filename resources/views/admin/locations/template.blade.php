@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed" id="content_container"></div>
    <div class="container-fixed">
        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    Countries
                </h1>
            </div>
            <div class="flex items-center gap-2.5">
                <a class="btn btn-sm btn-light" href="#">
                    Add New Country
                </a>
            </div>
        </div>
    </div>
    <!-- End of Container -->
    <!-- Container -->
    <div class="container-fixed">
        <div class="flex grow gap-5 lg:gap-7.5">
            
            <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                <div class="card pb-2.5">
                    <div class="card-header" id="basic_settings">
                        <h3 class="card-title">
                            Basic Settings
                        </h3>
                    </div>
                    <div class="card-body grid gap-5">
                        <div class="flex items-center flex-wrap gap-2.5">
                            <label class="form-label max-w-56">
                                Photo
                            </label>
                            <div class="flex items-center justify-between flex-wrap grow gap-2.5">
                                <span class="text-2sm text-gray-700">
                                    150x150px JPEG, PNG Image
                                </span>
                                <div class="image-input size-16" data-image-input="true">
                                    <input accept=".png, .jpg, .jpeg" name="avatar" type="file" />
                                    <input name="avatar_remove" type="hidden" />
                                    <div
                                        class="btn btn-icon btn-icon-xs btn-light shadow-default absolute z-1 size-5 -top-0.5 -end-0.5 rounded-full"
                                        data-image-input-remove=""
                                        data-tooltip="#image_input_tooltip"
                                        data-tooltip-trigger="hover"
                                    >
                                        <i class="ki-filled ki-cross"> </i>
                                    </div>
                                    <span class="tooltip" id="image_input_tooltip">
                                        Click to remove or revert
                                    </span>
                                    <div class="image-input-placeholder rounded-full border-2 border-success image-input-empty:border-gray-300" style="background-image: url(assets/media/avatars/blank.png);">
                                        <div class="image-input-preview rounded-full" style="background-image: url(assets/media/avatars/300-2.png);"></div>
                                        <div class="flex items-center justify-center cursor-pointer h-5 left-0 right-0 bottom-0 bg-dark-clarity absolute">
                                            <svg class="fill-light opacity-80" height="12" viewbox="0 0 14 12" width="14" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M11.6665 2.64585H11.2232C11.0873 2.64749 10.9538 2.61053 10.8382 2.53928C10.7225 2.46803 10.6295 2.36541 10.5698 2.24335L10.0448 1.19918C9.91266 0.931853 9.70808 0.707007 9.45438 0.550249C9.20068 0.393491 8.90806 0.311121 8.60984 0.312517H5.38984C5.09162 0.311121 4.799 0.393491 4.5453 0.550249C4.2916 0.707007 4.08701 0.931853 3.95484 1.19918L3.42984 2.24335C3.37021 2.36541 3.27716 2.46803 3.1615 2.53928C3.04584 2.61053 2.91234 2.64749 2.7765 2.64585H2.33317C1.90772 2.64585 1.49969 2.81486 1.19885 3.1157C0.898014 3.41654 0.729004 3.82457 0.729004 4.25002V10.0834C0.729004 10.5088 0.898014 10.9168 1.19885 11.2177C1.49969 11.5185 1.90772 11.6875 2.33317 11.6875H11.6665C12.092 11.6875 12.5 11.5185 12.8008 11.2177C13.1017 10.9168 13.2707 10.5088 13.2707 10.0834V4.25002C13.2707 3.82457 13.1017 3.41654 12.8008 3.1157C12.5 2.81486 12.092 2.64585 11.6665 2.64585ZM6.99984 9.64585C6.39413 9.64585 5.80203 9.46624 5.2984 9.12973C4.79478 8.79321 4.40225 8.31492 4.17046 7.75532C3.93866 7.19572 3.87802 6.57995 3.99618 5.98589C4.11435 5.39182 4.40602 4.84613 4.83432 4.41784C5.26262 3.98954 5.80831 3.69786 6.40237 3.5797C6.99644 3.46153 7.61221 3.52218 8.1718 3.75397C8.7314 3.98576 9.2097 4.37829 9.54621 4.88192C9.88272 5.38554 10.0623 5.97765 10.0623 6.58335C10.0608 7.3951 9.73765 8.17317 9.16365 8.74716C8.58965 9.32116 7.81159 9.64431 6.99984 9.64585Z"
                                                    fill=""
                                                ></path>
                                                <path
                                                    d="M7 8.77087C8.20812 8.77087 9.1875 7.7915 9.1875 6.58337C9.1875 5.37525 8.20812 4.39587 7 4.39587C5.79188 4.39587 4.8125 5.37525 4.8125 6.58337C4.8125 7.7915 5.79188 8.77087 7 8.77087Z"
                                                    fill=""
                                                ></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="w-full">
                            <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                <label class="form-label flex items-center gap-1 max-w-56">
                                    Name
                                </label>
                                <input class="input" type="text" value="Jason Tatum" />
                            </div>
                        </div>
                        <div class="w-full">
                            <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                <label class="form-label flex items-center gap-1 max-w-56">
                                    Company
                                </label>
                                <input class="input" type="text" value="KeenThemes" />
                            </div>
                        </div>
                        <div class="w-full">
                            <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                <label class="form-label flex items-center gap-1 max-w-56">
                                    Phone number
                                </label>
                                <input class="input" placeholder="Enter phone" type="text" />
                            </div>
                        </div>
                        <div class="flex items-center flex-wrap gap-2.5">
                            <label class="form-label max-w-56">
                                Visibility
                            </label>
                            <div class="grow">
                                <select class="select">
                                    <option>
                                        Public
                                    </option>
                                    <option>
                                        Option 2
                                    </option>
                                    <option>
                                        Option 3
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center flex-wrap gap-2.5">
                            <label class="form-label max-w-56">
                                Avaibality
                            </label>
                            <div class="grow">
                                <label class="switch">
                                    <span class="switch-label">
                                        Available to hire
                                    </span>
                                    <input checked="" type="checkbox" value="1" />
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end pt-2.5">
                            <button class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card pb-2.5">
                    <div class="card-header" id="auth_email">
                        <h3 class="card-title">
                            Email
                        </h3>
                    </div>
                    <div class="card-body grid gap-5 pt-7.5">
                        <div class="w-full">
                            <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                <label class="form-label max-w-56">
                                    Email
                                </label>
                                <div class="flex flex-col tems-start grow gap-7.5 w-full">
                                    <input class="input" type="text" value="jason@studio.io" />
                                    <div class="flex items-center gap-7.5">
                                        <label class="switch">
                                            <span class="switch-label">
                                                Active
                                            </span>
                                            <input checked="" type="checkbox" value="1" />
                                        </label>
                                        <label class="switch">
                                            <span class="switch-label">
                                                Primary
                                            </span>
                                            <input type="checkbox" value="2" />
                                        </label>
                                    </div>
                                    <span class="form-info text-gray-800 text-2sm font-normal">
                                        Input your email, designate as primary for priority updates. Toggle to seamlessly customize
                                        <br />
                                        your communication preferences
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header" id="auth_social_sign_in">
                        <h3 class="card-title">
                            Social Sign in
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="grid gap-5 mb-7">
                            <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 px-3.5 py-2.5">
                                <div class="flex items-center flex-wrap gap-3.5">
                                    <img alt="" class="size-6 shrink-0" src="assets/media/brand-logos/google.svg" />
                                    <div class="flex flex-col gap-0.5">
                                        <a class="text-sm font-medium text-gray-900 hover:text-primary-active" href="#">
                                            Google
                                        </a>
                                        <a class="text-2sm text-gray-700 hover:text-primary-active" href="#">
                                            jasontatum@ktstudio.io
                                        </a>
                                    </div>
                                </div>
                                <div class="flex items-center gap-5">
                                    <label class="switch switch-sm">
                                        <input checked="" type="checkbox" value="1" />
                                    </label>
                                    <div class="btn btn-sm btn-icon btn-light btn-clear">
                                        <i class="ki-filled ki-trash"> </i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 px-3.5 py-2.5">
                                <div class="flex items-center flex-wrap gap-3.5">
                                    <img alt="" class="size-6 shrink-0" src="assets/media/brand-logos/linkedin.svg" />
                                    <div class="flex flex-col gap-0.5">
                                        <a class="text-sm font-medium text-gray-900 hover:text-primary-active" href="#">
                                            Linkedin
                                        </a>
                                        <a class="text-2sm text-gray-700 hover:text-primary-active" href="#">
                                            jasontt@keenthemes.co
                                        </a>
                                    </div>
                                </div>
                                <div class="flex items-center gap-5">
                                    <label class="switch switch-sm">
                                        <input type="checkbox" value="1" />
                                    </label>
                                    <div class="btn btn-sm btn-icon btn-light btn-clear">
                                        <i class="ki-filled ki-trash"> </i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-0.5 mb-5">
                            <div class="text-md font-medium text-gray-900">
                                More Social Sign in options
                            </div>
                            <div class="text-2sm text-gray-800">
                                Effortless access awaits! Connect seamlessly with your preferred social account.
                            </div>
                        </div>
                        <div class="flex items-center flex-wrap gap-2.5 mb-7.5">
                            <a class="btn btn-light" href="#">
                                <img alt="" class="dark:hidden size-5" src="assets/media/brand-logos/apple-black.svg" />
                                <img alt="" class="light:hidden size-5" src="assets/media/brand-logos/apple-white.svg" />
                                Sign in with Apple
                            </a>
                            <a class="btn btn-light" href="#">
                                <img alt="" class="size-5" src="assets/media/brand-logos/microsoft-5.svg" />
                                Sign in with Microsoft
                            </a>
                            <a class="btn btn-light" href="#">
                                <img alt="" class="size-5" src="assets/media/brand-logos/facebook.svg" />
                                Sign in with Facebook
                            </a>
                        </div>
                        <div class="flex justify-end">
                            <button class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header" id="auth_two_factor">
                        <h3 class="card-title">
                            Two-Factor authentication(2FA)
                        </h3>
                        <div class="menu" data-menu="true">
                            <div class="menu-item" data-menu-item-offset="0, 10px" data-menu-item-placement="bottom-end" data-menu-item-placement-rtl="bottom-start" data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:click">
                                <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                    <i class="ki-filled ki-dots-vertical"> </i>
                                </button>
                                <div class="menu-dropdown menu-default w-full max-w-[200px]" data-menu-dismiss="true">
                                    <div class="menu-item">
                                        <a class="menu-link" href="html/demo1/account/home/settings-enterprise.html">
                                            <span class="menu-icon">
                                                <i class="ki-filled ki-setting-3"> </i>
                                            </span>
                                            <span class="menu-title">
                                                Settings
                                            </span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="html/demo1/account/members/import-members.html">
                                            <span class="menu-icon">
                                                <i class="ki-filled ki-some-files"> </i>
                                            </span>
                                            <span class="menu-title">
                                                Import
                                            </span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="html/demo1/account/activity.html">
                                            <span class="menu-icon">
                                                <i class="ki-filled ki-cloud-change"> </i>
                                            </span>
                                            <span class="menu-title">
                                                Activity
                                            </span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" data-modal-toggle="#report_user_modal" href="#">
                                            <span class="menu-icon">
                                                <i class="ki-filled ki-dislike"> </i>
                                            </span>
                                            <span class="menu-title">
                                                Report
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="grid gap-5 mb-7">
                            <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 px-3.5 py-2.5">
                                <div class="flex items-center flex-wrap gap-3.5">
                                    <div class="flex items-center">
                                        <div class="relative size-[50px] shrink-0">
                                            <svg class="w-full h-full stroke-gray-300 fill-gray-100" fill="none" height="48" viewbox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 
			18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 
			39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                                    fill=""
                                                ></path>
                                                <path
                                                    d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 
			18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 
			39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                                    stroke=""
                                                ></path>
                                            </svg>
                                            <div class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                                <i class="ki-filled ki-message-text-2 text-xl text-gray-500"> </i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-px">
                                        <a class="text-sm font-medium text-gray-900 hover:text-primary-active" href="#">
                                            Text Message (SMS)
                                        </a>
                                        <span class="text-2sm font-medium text-gray-700">
                                            Instant codes for secure account verification.
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 lg:gap-6">
                                    <label class="switch switch-sm">
                                        <input checked="" type="checkbox" value="1" />
                                    </label>
                                </div>
                            </div>
                            <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 px-3.5 py-2.5">
                                <div class="flex items-center flex-wrap gap-3.5">
                                    <div class="flex items-center">
                                        <div class="relative size-[50px] shrink-0">
                                            <svg class="w-full h-full stroke-gray-300 fill-gray-100" fill="none" height="48" viewbox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 
			18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 
			39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                                    fill=""
                                                ></path>
                                                <path
                                                    d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 
			18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 
			39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                                    stroke=""
                                                ></path>
                                            </svg>
                                            <div class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                                <i class="ki-filled ki-shield-tick text-xl text-gray-500"> </i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-px">
                                        <a class="text-sm font-medium text-gray-900 hover:text-primary-active" href="#">
                                            Authenticator App (TOTP)
                                        </a>
                                        <span class="text-2sm font-medium text-gray-700">
                                            Elevate protection with an authenticator app for two-factor authentication.
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 lg:gap-6">
                                    <label class="switch switch-sm">
                                        <input type="checkbox" value="1" />
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="w-full">
                            <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5 mb-7">
                                <label class="form-label max-w-56">
                                    Password
                                </label>
                                <div class="flex flex-col tems-start grow gap-3 w-full">
                                    <input class="input" placeholder="Enter password" type="text" />
                                    <span class="form-info gray-800 font-normal">
                                        Enter your password to setup Two-Factor authentication
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end pt-2.5">
                            <button class="btn btn-primary">
                                Setup
                            </button>
                        </div>
                    </div>
                </div>
                <style>
                    .singl-sign-on-bg {
                        background-image: url("assets/media/images/2600x1600/bg-2.png");
                    }
                    .dark .singl-sign-on-bg {
                        background-image: url("assets/media/images/2600x1600/bg-2-dark.png");
                    }
                </style>
                <div class="card">
                    <div class="card-header" id="auth_social_sign_in_sso">
                        <h3 class="card-title">
                            Single Sign On(SSO)
                        </h3>
                    </div>
                    <div class="card-body flex flex-col gap-7.5 divide-y divide-gray-200">
                        <div class="grid gap-7 pt-7.5">
                            <div class="text-md font-semibold text-gray-900">
                                1. Select SSO integration Type
                            </div>
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
                                <label class="flex align-stretch cursor-pointer bg-center h-44 bg-no-repeat border border-gray-300 rounded-xl border-dashed has-[:checked]:border-primary bg-[length:500px] sso-active singl-sign-on-bg">
                                    <div class="flex flex-col place-items-center place-content-center rounded-xl grow">
                                        <div class="flex items-center h-11">
                                            <img alt="" class="w-5" src="assets/media/brand-logos/azure.svg" />
                                        </div>
                                        <span class="text-md font-medium text-gray-900">
                                            Microsoft Azure
                                        </span>
                                        <input class="appearance-none" name="sso_option" type="radio" value="1" />
                                    </div>
                                </label>
                                <label class="flex align-stretch cursor-pointer bg-center h-44 bg-no-repeat border border-gray-300 rounded-xl border-dashed has-[:checked]:border-primary bg-[length:500px] sso-active singl-sign-on-bg">
                                    <div class="flex flex-col place-items-center place-content-center rounded-xl grow">
                                        <div class="flex items-center h-11">
                                            <img alt="" class="w-8" src="assets/media/brand-logos/google.svg" />
                                        </div>
                                        <span class="text-md font-medium text-gray-900">
                                            Google
                                        </span>
                                        <input checked="" class="appearance-none" name="sso_option" type="radio" value="1" />
                                    </div>
                                </label>
                                <label class="flex align-stretch cursor-pointer bg-center h-44 bg-no-repeat border border-gray-300 rounded-xl border-dashed has-[:checked]:border-primary bg-[length:500px] sso-active singl-sign-on-bg">
                                    <div class="flex flex-col place-items-center place-content-center rounded-xl grow">
                                        <div class="flex items-center h-11">
                                            <img alt="" class="w-24" src="assets/media/brand-logos/openid.svg" />
                                        </div>
                                        <span class="text-md font-medium text-gray-900">
                                            OpenID Connect
                                        </span>
                                        <input class="appearance-none" name="sso_option" type="radio" value="1" />
                                    </div>
                                </label>
                            </div>
                            <style>
                                .sso-active:has(:checked) {
                                    background-image: url("assets/media/images/2600x1600/bg-1.png");
                                }
                                .dark .sso-active:has(:checked) {
                                    background-image: url("assets/media/images/2600x1600/bg-1-dark.png");
                                }
                            </style>
                        </div>
                        <div class="grid gap-7 pt-7.5">
                            <div class="text-md font-semibold text-gray-900">
                                2. Configure Google authentication
                            </div>
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                    <label class="form-label max-w-56">
                                        Client ID
                                    </label>
                                    <input class="input" type="text" value="02874374-367145773" />
                                </div>
                            </div>
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                    <label class="form-label max-w-56">
                                        Client Secret
                                    </label>
                                    <input class="input" type="text" value="23djfn784957f8022we2232307822-cey2442" />
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button class="btn btn-primary">
                                    Save Changes
                                </button>
                            </div>
                        </div>
                        <div class="grid gap-7 pt-7.5">
                            <div class="text-md font-semibold text-gray-900">
                                3. Note down custom URL for Google SSO authentication
                            </div>
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                    <label class="form-label max-w-56">
                                        Custom Login UTL
                                    </label>
                                    <div class="grow">
                                        <div class="input-group">
                                            <input class="input" type="text" value="https://devs.keenthemes.com/rl/AirMikeStudios" />
                                            <span class="btn btn-primary">
                                                Copy
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-info pt-7.5 pb-5 text-gray-800 font-normal">
                            Single Sign-On (SSO) authentication streamlines access across multiple platforms. Users log in once, gaining seamless entry
                            <br />
                            to various systems without repetitive credentials.
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header" id="auth_password">
                        <h3 class="card-title">
                            Password
                        </h3>
                    </div>
                    <div class="card-body grid gap-5">
                        <div class="w-full">
                            <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                <label class="form-label max-w-56">
                                    Current Password
                                </label>
                                <input class="input" placeholder="Your current password" type="text" value="" />
                            </div>
                        </div>
                        <div class="w-full">
                            <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                <label class="form-label max-w-56">
                                    New Password
                                </label>
                                <input class="input" placeholder="New password" type="text" value="" />
                            </div>
                        </div>
                        <div class="w-full">
                            <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                                <label class="form-label max-w-56">
                                    Confirm New Password
                                </label>
                                <input class="input" placeholder="Confirm new password" type="text" value="" />
                            </div>
                        </div>
                        <div class="flex justify-end pt-2.5">
                            <button class="btn btn-primary">
                                Reset Password
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header" id="advanced_settings_preferences">
                        <h3 class="card-title">
                            Preferences
                        </h3>
                    </div>
                    <div class="card-body grid gap-5 lg:py-7.5">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="form-label max-w-56">
                                Language
                            </label>
                            <select class="select">
                                <option>
                                    American English
                                </option>
                                <option>
                                    Option 2
                                </option>
                                <option>
                                    Option 3
                                </option>
                            </select>
                        </div>
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="form-label max-w-56">
                                Time zone
                            </label>
                            <div class="grow">
                                <select class="select">
                                    <option>
                                        GMT -5:00 - Eastern Time(US & Canada)
                                    </option>
                                    <option>
                                        Option 2
                                    </option>
                                    <option>
                                        Option 3
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5 mb-2">
                            <label class="form-label max-w-56">
                                Currency
                            </label>
                            <div class="grow">
                                <select class="select">
                                    <option>
                                        United States Dollar (USD)
                                    </option>
                                    <option>
                                        Option 2
                                    </option>
                                    <option>
                                        Option 3
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="form-label max-w-56">
                                Open tasks as...
                            </label>
                            <div class="flex items-center gap-5">
                                <label class="radio-group">
                                    <input checked="" class="radio" name="open-tasks" type="radio" value="1" />
                                    <span class="radio-label">
                                        Modal
                                    </span>
                                </label>
                                <label class="radio-group">
                                    <input class="radio" name="open-tasks" type="radio" value="2" />
                                    <span class="radio-label">
                                        Fullscreen
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2.5 mb-1.5">
                            <label class="form-label max-w-56">
                                Attributes
                            </label>
                            <div class="flex flex-col items-start gap-5">
                                <div class="flex flex-col gap-2.5">
                                    <label class="checkbox-group">
                                        <input class="checkbox" name="attributes" type="checkbox" value="1" />
                                        <span class="checkbox-label">
                                            Show list names
                                        </span>
                                    </label>
                                    <div class="form-hint">
                                        See the name next to each icon
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2.5">
                                    <label class="checkbox-group">
                                        <input checked="" class="checkbox" name="attributes" type="checkbox" value="2" />
                                        <span class="checkbox-label">
                                            Show linked task names
                                        </span>
                                    </label>
                                    <div class="form-hint">
                                        Show task names next to ids for linked project tasks.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center flex-wrap gap-2.5">
                            <label class="form-label max-w-56">
                                Email visibility
                            </label>
                            <label class="switch">
                                <input checked="" type="checkbox" value="1" />
                                <span class="text-gray-800 text-sm">
                                    Visible
                                </span>
                            </label>
                        </div>
                        <div class="flex justify-end">
                            <button class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header" id="advanced_settings_appearance">
                        <h3 class="card-title">
                            Appearance
                        </h3>
                    </div>
                    <div class="card-body lg:py-7.5">
                        <div class="mb-5">
                            <h3 class="text-md font-medium text-gray-900">
                                Theme mode
                            </h3>
                            <span class="text-2sm text-gray-700">
                                Select or customize your ui theme
                            </span>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
                            <div>
                                <label
                                    class="flex items-end border bg-no-repeat bg-cover border-gray-300 rounded-xl has-[:checked]:border-success has-[:checked]:border-3 [&_.checked]:has-[:checked]:flex h-[170px] mb-0.5"
                                    style="background-image: url('assets/media/images/600x400/28.jpg');"
                                >
                                    <input checked="" class="appearance-none" name="appearance_option" type="radio" value="2" />
                                    <i class="ki-solid ki-check-circle checked ml-5 mb-5 text-xl text-success leading-none hidden"> </i>
                                </label>
                                <span class="text-sm font-medium text-gray-900">
                                    Dark
                                </span>
                            </div>
                            <div>
                                <label
                                    class="flex items-end border bg-no-repeat bg-cover border-gray-300 rounded-xl has-[:checked]:border-success has-[:checked]:border-3 [&_.checked]:has-[:checked]:flex h-[170px] mb-0.5"
                                    style="background-image: url('assets/media/images/600x400/32.jpg');"
                                >
                                    <input class="appearance-none" name="appearance_option" type="radio" value="2" />
                                    <i class="ki-solid ki-check-circle checked ml-5 mb-5 text-xl text-success leading-none hidden"> </i>
                                </label>
                                <span class="text-sm font-medium text-gray-900">
                                    Light
                                </span>
                            </div>
                            <div>
                                <label
                                    class="flex items-end border bg-no-repeat bg-cover border-gray-300 rounded-xl has-[:checked]:border-success has-[:checked]:border-3 [&_.checked]:has-[:checked]:flex h-[170px] mb-0.5"
                                    style="background-image: url('assets/media/images/600x400/30.jpg');"
                                >
                                    <input class="appearance-none" name="appearance_option" type="radio" value="2" />
                                    <i class="ki-solid ki-check-circle checked ml-5 mb-5 text-xl text-success leading-none hidden"> </i>
                                </label>
                                <span class="text-sm font-medium text-gray-900">
                                    Sistem
                                </span>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 mt-7 mb-8"></div>
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5 mb-8">
                            <label class="form-label max-w-48 text-gray-800 font-normal">
                                Transparent sidebar
                            </label>
                            <div class="flex items-center gap-7.5 grow">
                                <label class="switch">
                                    <span class="switch-label text-700 font-semibold">
                                        Active
                                    </span>
                                    <input checked="" type="checkbox" value="1" />
                                </label>
                                <span class="form-info text-gray-800 font-normal">
                                    Toggle the transparent sidebar for a sleek interface.Switch it on for transparency or off for a solid background.
                                </span>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header" id="advanced_settings_notifications">
                        <h3 class="card-title">
                            Notifications
                        </h3>
                    </div>
                    <div class="card-body lg:py-7.5">
                        <div class="flex flex-wrap items-center gap-5 mb-5 lg:mb-7">
                            <div class="flex items-center justify-between flex-wrap grow border border-gray-200 rounded-xl gap-2 px-3.5 py-2.5">
                                <div class="flex items-center flex-wrap gap-3.5">
                                    <div class="relative size-[50px] shrink-0">
                                        <svg class="w-full h-full stroke-gray-300 fill-gray-100" fill="none" height="48" viewbox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 
			18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 
			39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                                fill=""
                                            ></path>
                                            <path
                                                d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 
			18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 
			39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                                stroke=""
                                            ></path>
                                        </svg>
                                        <div class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                            <i class="ki-filled ki-sms text-xl text-gray-500"> </i>
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <a class="text-sm font-medium text-gray-900 hover:text-primary-active mb-px" href="#">
                                            Email
                                        </a>
                                        <span class="text-2sm text-gray-700">
                                            Tailor Your Email Preferences.
                                        </span>
                                    </div>
                                </div>
                                <label class="switch switch-sm">
                                    <input checked="" type="checkbox" value="1" />
                                </label>
                            </div>
                            <div class="flex items-center justify-between flex-wrap grow border border-gray-200 rounded-xl gap-2 px-3.5 py-2.5">
                                <div class="flex items-center flex-wrap gap-3.5">
                                    <div class="relative size-[50px] shrink-0">
                                        <svg class="w-full h-full stroke-gray-300 fill-gray-100" fill="none" height="48" viewbox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 
			18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 
			39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                                fill=""
                                            ></path>
                                            <path
                                                d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 
			18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 
			39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                                stroke=""
                                            ></path>
                                        </svg>
                                        <div class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                            <img alt="" class="h-5" src="assets/media/brand-logos/slack.svg" />
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <a class="text-sm font-medium text-gray-900 hover:text-primary-active mb-px" href="#">
                                            Slack
                                        </a>
                                        <span class="text-2sm text-gray-700">
                                            Stay Updated on Slack.
                                        </span>
                                    </div>
                                </div>
                                <label class="switch switch-sm">
                                    <input checked="" type="checkbox" value="1" />
                                </label>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3.5 mb-7">
                            <span class="text-md font-medium text-gray-900 pb-0.5">
                                Desktop notifications
                            </span>
                            <div class="flex flex-col items-start gap-4">
                                <label class="radio-group">
                                    <input class="radio" name="desktop_notification" type="radio" value="1" />
                                    <span class="radio-label radio-label-sm">
                                        All new messages (Recommended)
                                    </span>
                                </label>
                                <label class="radio-group">
                                    <input checked="" class="radio" name="desktop_notification" type="radio" value="2" />
                                    <span class="radio-label radio-label-sm">
                                        Direct @mentions
                                    </span>
                                </label>
                                <label class="radio-group">
                                    <input checked="" class="radio" name="desktop_notification" type="radio" value="3" />
                                    <span class="radio-label radio-label-sm">
                                        Disabled
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3.5 mb-7">
                            <span class="text-md font-medium text-gray-900 pb-0.5">
                                Email notifications
                            </span>
                            <div class="flex flex-col items-start gap-4">
                                <label class="radio-group">
                                    <input class="radio" name="email_notification" type="radio" value="1" />
                                    <span class="radio-label radio-label-sm">
                                        All new messages and statuses
                                    </span>
                                </label>
                                <label class="radio-group">
                                    <input checked="" class="radio" name="email_notification" type="radio" value="2" />
                                    <span class="radio-label radio-label-sm text-gray-800 font-medium">
                                        AUnread messages and statuses
                                    </span>
                                </label>
                                <label class="radio-group">
                                    <input checked="" class="radio" name="email_notification" type="radio" value="3" />
                                    <span class="radio-label radio-label-sm">
                                        Disabled
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3.5">
                            <span class="text-md font-medium text-gray-900 pb-0.5">
                                Subscriptions
                            </span>
                            <label class="checkbox-group">
                                <input class="checkbox" name="check" type="checkbox" value="1" />
                                <span class="checkbox-label checkbox-label-sm">
                                    Automatically subscribe to tasks you create
                                </span>
                            </label>
                        </div>
                        <div class="flex justify-end">
                            <button class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header" id="advanced_settings_address">
                        <h3 class="card-title">
                            Address
                        </h3>
                    </div>
                    <div class="card-body grid gap-5 lg:py-7.5">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="form-label flex items-center gap-1 max-w-56">
                                Address
                            </label>
                            <input class="input" type="text" value="Avinguda Imaginària, 789" />
                        </div>
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="form-label max-w-56">
                                Country
                            </label>
                            <div class="grow">
                                <select class="select" name="select">
                                    <option value="1">
                                        Spain
                                    </option>
                                    <option value="2">
                                        Option 2
                                    </option>
                                    <option value="3">
                                        Option 3
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="form-label max-w-56">
                                State
                            </label>
                            <input class="input" placeholder="State" type="text" value="" />
                        </div>
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="form-label max-w-56">
                                City
                            </label>
                            <input class="input" type="text" value="Barcelona" />
                        </div>
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="form-label max-w-56">
                                Postcode
                            </label>
                            <input class="input" type="text" value="08012" />
                        </div>
                        <div class="flex justify-end pt-2.5">
                            <button class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
                <style>
                    .user-access-bg {
                        background-image: url("assets/media/images/2600x1200/bg-5.png");
                    }
                    .dark .user-access-bg {
                        background-image: url("assets/media/images/2600x1200/bg-5-dark.png");
                    }
                </style>
                <div class="card">
                    <div class="card-header mb-5" id="external_services_manage_api">
                        <h3 class="card-title">
                            Manage API
                        </h3>
                    </div>
                    <div class="card-body lg:py-7.5 grid gap-5 lg:gap-7.5">
                        <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                            <label class="form-label max-w-56 text-gray-800 font-normal">
                                API Key
                            </label>
                            <label class="input">
                                <input placeholder="Right icon" type="text" value="abc123xyz456sample789key000" />
                                <button class="btn btn-icon">
                                    <i class="ki-filled ki-copy"> </i>
                                </button>
                            </label>
                        </div>
                        <div
                            class="flex items-center flex-wrap sm:flex-nowrap justify-between grow border border-gray-200 rounded-xl gap-2 p-5 rtl:[background-position:-195px_-85px] [background-position:195px_-85px] bg-no-repeat bg-[length:650px] user-access-bg"
                        >
                            <div class="flex items-center gap-4">
                                <div class="relative size-[50px] shrink-0">
                                    <svg class="w-full h-full stroke-brand-clarity fill-brand-light" fill="none" height="48" viewbox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 
			18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 
			39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                            fill=""
                                        ></path>
                                        <path
                                            d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 
			18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 
			39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                            stroke=""
                                        ></path>
                                    </svg>
                                    <div class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                        <i class="ki-filled ki-security-user text-xl text-brand"> </i>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center flex-wrap gap-2.5">
                                        <a class="text-base font-medium text-gray-900 hover:text-primary-active" href="#">
                                            User Access
                                        </a>
                                        <span class="badge badge-sm badge-outline shrink-0">
                                            16 days left
                                        </span>
                                    </div>
                                    <div class="form-info text-gray-800 font-normal">
                                        This API key can only access
                                        <a class="link" href="https://keenthemes.com/">
                                            @keenthemes
                                        </a>
                                        <br />
                                        Secure access with a unique API key for enhanced functionality.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button class="btn btn-sm btn-dark">
                                    Renew Plan
                                </button>
                                <a class="btn btn-sm btn-clear btn-light" href="#">
                                    Docs
                                </a>
                            </div>
                        </div>
                        <p class="text-2sm text-gray-800">
                            Unlock the full potential of your application with our API, a secure gateway facilitating seamless integration, empowering developers to create innovative and dynamic experiences effortlessly.
                        </p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header" id="external_services_integrations">
                        <h3 class="card-title">
                            Integrations
                        </h3>
                    </div>
                    <div class="card-body grid gap-5 lg:gap-7.5 lg:py-7.5 py-5">
                        <div class="grid gap-5">
                            <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 p-3.5">
                                <div class="flex items-center flex-wrap gap-3.5">
                                    <img alt="" class="size-8 shrink-0" src="assets/media/brand-logos/google-webdev.svg" />
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5">
                                            <a class="text-sm font-medium text-gray-900 hover:text-primary-active" href="#">
                                                Google web.dev
                                            </a>
                                            <a class="text-2sm text-gray-700 hover:text-primary-active" href="#">
                                                webdev@webdevmail.com
                                            </a>
                                        </div>
                                        <span class="text-2sm text-gray-700">
                                            Integrate for enhanced collaboration in web development.
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 lg:gap-5">
                                    <label class="switch switch-sm">
                                        <input checked="" type="checkbox" value="1" />
                                    </label>
                                    <div class="btn btn-sm btn-icon btn-clear btn-light">
                                        <i class="ki-filled ki-setting-2"> </i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 p-3.5">
                                <div class="flex items-center flex-wrap gap-3.5">
                                    <img alt="" class="size-8 shrink-0" src="assets/media/brand-logos/equacoin.svg" />
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5">
                                            <a class="text-sm font-medium text-gray-900 hover:text-primary-active" href="#">
                                                Equacoin
                                            </a>
                                            <a class="text-2sm text-gray-700 hover:text-primary-active" href="#">
                                                equacoin@cryptoemail.com
                                            </a>
                                        </div>
                                        <span class="text-2sm text-gray-700">
                                            Streamline cryptocurrency transactions securely and efficiently.
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 lg:gap-5">
                                    <label class="switch switch-sm">
                                        <input type="checkbox" value="2" />
                                    </label>
                                    <div class="btn btn-sm btn-icon btn-clear btn-light">
                                        <i class="ki-filled ki-setting-2"> </i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 p-3.5">
                                <div class="flex items-center flex-wrap gap-3.5">
                                    <img alt="" class="size-8 shrink-0" src="assets/media/brand-logos/evernote.svg" />
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5">
                                            <a class="text-sm font-medium text-gray-900 hover:text-primary-active" href="#">
                                                Evernote
                                            </a>
                                            <a class="text-2sm text-gray-700 hover:text-primary-active" href="#">
                                                evernote@noteexample.com
                                            </a>
                                        </div>
                                        <span class="text-2sm text-gray-700">
                                            Streamline cryptocurrency transactions securely and efficiently.
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 lg:gap-5">
                                    <label class="switch switch-sm">
                                        <input checked="" type="checkbox" value="1" />
                                    </label>
                                    <div class="btn btn-sm btn-icon btn-clear btn-light">
                                        <i class="ki-filled ki-setting-2"> </i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 p-3.5">
                                <div class="flex items-center flex-wrap gap-3.5">
                                    <img alt="" class="size-8 shrink-0" src="assets/media/brand-logos/inferno.svg" />
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5">
                                            <a class="text-sm font-medium text-gray-900 hover:text-primary-active" href="#">
                                                Inferno
                                            </a>
                                            <a class="text-2sm text-gray-700 hover:text-primary-active" href="#">
                                                inferno@dataexample.com
                                            </a>
                                        </div>
                                        <span class="text-2sm text-gray-700">
                                            Robust email integration for data management.
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 lg:gap-5">
                                    <label class="switch switch-sm">
                                        <input checked="" type="checkbox" value="1" />
                                    </label>
                                    <div class="btn btn-sm btn-icon btn-clear btn-light">
                                        <i class="ki-filled ki-setting-2"> </i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between flex-wrap border border-gray-200 rounded-xl gap-2 p-3.5">
                                <div class="flex items-center flex-wrap gap-3.5">
                                    <img alt="" class="size-8 shrink-0" src="assets/media/brand-logos/jira.svg" />
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5">
                                            <a class="text-sm font-medium text-gray-900 hover:text-primary-active" href="#">
                                                Jira
                                            </a>
                                            <a class="text-2sm text-gray-700 hover:text-primary-active" href="#">
                                                jira@projectmail.com
                                            </a>
                                        </div>
                                        <span class="text-2sm text-gray-700">
                                            Streamline project management, enhance collaboration.
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 lg:gap-5">
                                    <label class="switch switch-sm">
                                        <input type="checkbox" value="2" />
                                    </label>
                                    <div class="btn btn-sm btn-icon btn-clear btn-light">
                                        <i class="ki-filled ki-setting-2"> </i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header" id="delete_account">
                        <h3 class="card-title">
                            Delete Account
                        </h3>
                    </div>
                    <div class="card-body flex flex-col lg:py-7.5 lg:gap-7.5 gap-3">
                        <div class="flex flex-col gap-5">
                            <div class="text-2sm text-gray-800">
                                We regret to see you leave. Confirm account deletion below. Your data will be permanently removed. Thank you for being part of our community. Please check our
                                <a class="link" href="#">
                                    Setup Guidelines
                                </a>
                                if you still wish continue.
                            </div>
                            <label class="checkbox-group">
                                <input class="checkbox checkbox-sm" name="delete" type="checkbox" value="1" />
                                <span class="checkbox-label">
                                    Confirm deleting account
                                </span>
                            </label>
                        </div>
                        <div class="flex justify-end gap-2.5">
                            <button class="btn btn-light">
                                Deactivate Instead
                                <button class="btn btn-danger">
                                    Delete Account
                                </button>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</main>

@endsection