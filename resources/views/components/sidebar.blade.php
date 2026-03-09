<aside class="sidebar">
    <button type="button" class="sidebar-close-btn !mt-4">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="{{ route('index') }}" class="sidebar-logo">
            <img src="{{ asset('assets/images/logo-light.svg') }}" alt="site logo" class="light-logo">
            <img src="{{ asset('assets/images/logo-dark.svg') }}" alt="site logo" class="dark-logo">
            <img src="{{ asset('assets/images/logo-icon.png') }}" alt="site logo" class="logo-icon">
        </a>
    </div>
    <div class="sidebar-menu-area">
        @php
            $currentUser = auth()->user();
            $canAccessLeadModule = $currentUser?->hasModule('lead_management') ?? false;
            $canAccessCampaignModule = $currentUser?->hasModule('campaign_management') ?? false;
            $canCreateLead = $currentUser?->hasModulePermission('lead_management', 'create_lead') ?? false;
            $canManageFollowups = $currentUser?->hasModulePermission('lead_management', 'manage_followups') ?? false;
            $canViewLeads = $currentUser?->hasModulePermission('lead_management', 'view_leads') ?? false;
            $canViewCampaigns = $currentUser?->hasModulePermission('campaign_management', 'view_campaigns') ?? false;
            $hasAnyLeadUiPermission = $canCreateLead || $canManageFollowups || $canViewLeads;
        @endphp
        <ul class="sidebar-menu" id="sidebar-menu">
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('crmDashboard') }}"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> CRM</a>
                    </li>
                </ul>
            </li>
            @if ($canAccessLeadModule && $hasAnyLeadUiPermission)
                <li class="dropdown">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="mdi:lead-pencil" class="menu-icon"></iconify-icon>
                        <span>Lead Management</span>
                    </a>
                    <ul class="sidebar-submenu">
                        @if ($canCreateLead)
                            <li>
                                <a href="{{ route('clinicManualLead') }}"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Create New Lead</a>
                            </li>
                        @endif
                        @if ($canManageFollowups)
                            <li>
                                <a href="{{ route('clinicAppointments') }}"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Follow-up Queue</a>
                            </li>
                        @endif
                        @if ($canViewLeads)
                            <li>
                                <a href="{{ route('clinicLeads') }}"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> All Leads</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if ($canAccessCampaignModule && $canViewCampaigns)
                <li>
                    <a href="{{ route('email') }}">
                        <iconify-icon icon="mdi:message-text" class="menu-icon"></iconify-icon>
                        <span>Campaign Management</span>
                    </a>
                </li>
            @endif
            @if ($currentUser?->isAdmin())
                <li>
                    <a href="{{ route('usersList') }}">
                        <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
                        <span>User Management</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</aside>
