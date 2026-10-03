<script>
  window.careWorkspace = {
    panel: {{ Illuminate\Support\Js::from($panel) }},
    csrfToken: {{ Illuminate\Support\Js::from(csrf_token()) }},
    logoutUrl: {{ Illuminate\Support\Js::from(route('staff.logout')) }},
    user: {{ Illuminate\Support\Js::from([
        'name' => $user->name,
        'role' => $user->role?->value,
        'clinicianId' => $user->clinician?->id,
        'avatarUrl' => $user->avatar_url,
        'initials' => $user->initials,
    ]) }},
    permissions: {
      manageClinicians: {{ Illuminate\Support\Js::from($canManageClinicians) }},
      accessSettings: {{ Illuminate\Support\Js::from($canAccessSettings ?? false) }},
    },
    navigation: {{ Illuminate\Support\Js::from($navigation) }},
    api: {
      clinicians: {{ Illuminate\Support\Js::from($apiBase.'/clinicians') }},
      clinicianOnboardings: {{ Illuminate\Support\Js::from($apiBase.'/clinician-onboardings') }},
      patients: {{ Illuminate\Support\Js::from($apiBase.'/patients') }},
      appointments: {{ Illuminate\Support\Js::from($apiBase.'/appointments') }},
      tasks: {{ Illuminate\Support\Js::from($apiBase.'/tasks') }},
      appointmentStatus: {{ Illuminate\Support\Js::from($apiBase.'/appointments') }},
      availability: {{ Illuminate\Support\Js::from($apiBase.'/availability') }},
      services: {{ Illuminate\Support\Js::from($panel === 'admin' ? $apiBase.'/services' : url('/booking-options')) }},
      account: {{ Illuminate\Support\Js::from($apiBase.'/account') }},
      settings: {{ Illuminate\Support\Js::from($apiBase.'/settings') }},
    },
  };
</script>
