<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Symfony\Component\HttpFoundation\Response;

class FailedPasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponseContract
{
    /**
     * Create a new response instance.
     *
     * @param  string  $status  broker status key; deliberately ignored
     * @return void
     */
    public function __construct(string $status) {}

    /**
     * FR-4a: every failed link request (unknown email, broker throttling)
     * renders the success status verbatim so no response reveals whether
     * the account exists — mirrors the FR-4 no-oracle decision.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $status = trans('passwords.sent');

        return $request->wantsJson()
            ? new JsonResponse(['message' => $status], 200)
            : back()->with('status', $status);
    }
}
