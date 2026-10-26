<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipRequest;
use App\Models\MemberType;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', ['active' => Member::currentlyActive()->count(), 'archived' => Member::onlyTrashed()->count(), 'pending' => MembershipRequest::where('status', 'pending')->count(), 'types' => MemberType::withCount(['members' => fn ($q) => $q->currentlyActive()])->get()]);
    }
}
