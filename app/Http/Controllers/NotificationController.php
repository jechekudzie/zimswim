<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The bell in the backend header.
 *
 * Notices are written to the notifications table alongside the email, so a
 * person who deleted the email, or never had it, still finds the thing that
 * needs doing when they sign in.
 */
class NotificationController extends Controller
{
    /**
     * Mark one notice read and go to whatever it was about.
     */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);

        $record->markAsRead();

        $url = is_string($record->data['url'] ?? null) ? $record->data['url'] : null;

        return $url === null ? back() : redirect()->to($this->localPath($url));
    }

    /**
     * Clear the tally without opening anything.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    /**
     * The path a stored link points at, on this host.
     *
     * A notice is usually written by a queued job, where a URL is built from
     * APP_URL rather than from the request, so the stored host can differ
     * from the one the person is actually browsing. Taking only the path
     * sends them to the right place on the site they are already on, and
     * means a stored link can never redirect anybody off it.
     */
    private function localPath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        return (is_string($path) ? $path : '/').(is_string($query) ? '?'.$query : '');
    }
}
