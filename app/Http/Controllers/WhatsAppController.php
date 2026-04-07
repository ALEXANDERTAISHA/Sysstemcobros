<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\WhatsappNotification;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function index(Request $request)
    {
        $notifications = WhatsappNotification::orderByDesc('created_at')->paginate(30);
        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $hasCallMeBot = !empty(config('services.callmebot.api_key'));
        $hasMeta = !empty(config('services.meta_whatsapp.token'))
            && !empty(config('services.meta_whatsapp.phone_number_id'));
        $hasApiKey = $hasCallMeBot || $hasMeta;

        return view('whatsapp.index', compact('notifications', 'clients', 'hasApiKey'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'phone'   => 'required|string|max:30',
            'message' => 'required|string|max:1000',
            'name'    => 'nullable|string|max:150',
        ]);

        $notification = $this->whatsApp->send($data['phone'], $data['message'], $data['name'] ?? null);

        if ($notification->status === 'sent') {
            return back()->with('success', 'Mensaje enviado correctamente.');
        }

        // Fallback WhatsApp Web link
        $link = $this->whatsApp->chatUrl($data['phone'], $data['message']);
        return back()->with('info', "API no configurada. <a href=\"{$link}\" target=\"_blank\" class=\"alert-link\">Abrir WhatsApp Web para enviar</a>.");
    }
}
