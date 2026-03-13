<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateUserAction;
use App\DataTransferObjects\CreateUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\GetUsersRequest;
use App\Http\Resources\UserCreatedResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

/**
 * Slim controller — request validation and response shaping only.
 *
 * Write path is handled by CreateUserAction (CQRS-lite).
 * Read path is handled by UserService.
 * can_edit resolution lives here because it is a presentation-time concern
 * (it depends on who is viewing the list, not on domain rules) and requires
 * the Gate which should not be called from the Service layer.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly CreateUserAction $createUser,
        private readonly UserService $userService,
    ) {}

    /**
     * POST /api/users
     *
     * Create a new user. Returns the created resource with HTTP 201.
     * Welcome and admin notification emails are dispatched asynchronously
     * inside the Action layer.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->createUser->execute(
            CreateUserData::fromRequest($request->validated())
        );

        return new JsonResponse(new UserCreatedResource($user), 201);
    }

    /**
     * GET /api/users
     *
     * Return a paginated, filtered list of active users.
     *
     * can_edit is stamped onto each User model instance here — after the
     * Policy check — so that UserResource can conditionally include it.
     * The route requires auth:sanctum so $request->user() is always present.
     */
    public function index(GetUsersRequest $request): JsonResponse
    {
        $paginator = $this->userService->getActiveUsers($request->validated());
        $authUser  = $request->user();

        $users = UserResource::collection(
            $paginator->getCollection()->map(function (User $user) use ($authUser) {
                if ($authUser) {
                    $user->can_edit = $authUser->can('update', $user);
                }

                return $user;
            })
        );

        return new JsonResponse([
            'page'  => $paginator->currentPage(),
            'users' => $users,
        ]);
    }
}
