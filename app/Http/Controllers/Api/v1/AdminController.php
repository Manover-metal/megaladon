<?php

namespace App\Http\Controllers\Api\v1;

use App\Admin\AdminError;
use App\Admin\Engine;
use App\Admin\Resources;
use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * API для Next.js-админки (megaladon_admin). Разделы и их логика — в
 * App\Admin\Resources, здесь только маршрутизация и ответы.
 */
class AdminController extends ApiController
{
    public function login(Request $request)
    {
        $login = trim((string) $request->input('login'));
        // Телефон в базе — +7XXXXXXXXXX; принимаем 8/7/+7 с любыми разделителями, либо email.
        $digits = preg_replace('/^8(?=\d{10}$)/', '7', preg_replace('/\D/', '', $login));
        $user = User::where('phone', "+$digits")->orWhere('email', $login)->first();

        if (!$user || !Hash::check((string) $request->input('password'), $user->password)) {
            return response()->json(['message' => 'Неверный телефон или пароль'], 401);
        }
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Нет доступа в админку'], 403);
        }
        if ($user->isBanned()) {
            return response()->json(['message' => 'Аккаунт заблокирован'], 403);
        }

        return response()->json([
            'token' => $user->createToken('admin')->plainTextToken,
            'admin' => ['id' => $user->id, 'name' => $user->name],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([]);
    }

    public function me(Request $request)
    {
        return response()->json(['id' => $request->user()->id, 'name' => $request->user()->name]);
    }

    public function stats()
    {
        return response()->json(Resources::stats());
    }

    public function badges()
    {
        return response()->json(Resources::badges());
    }

    public function index(string $resource, Request $request)
    {
        return response()->json($this->engine($resource)->list($request->query()));
    }

    public function options(string $resource)
    {
        return response()->json($this->engine($resource)->options());
    }

    public function show(string $resource, int $id)
    {
        $data = $this->engine($resource)->get($id);

        return $data ? response()->json($data) : response()->json(['message' => 'Запись не найдена'], 404);
    }

    public function store(string $resource, Request $request)
    {
        return $this->save($this->engine($resource, 'create'), null, $request);
    }

    public function update(string $resource, int $id, Request $request)
    {
        return $this->save($this->engine($resource, 'edit'), $id, $request);
    }

    public function destroy(string $resource, int $id)
    {
        $this->engine($resource, 'delete')->remove($id);

        return response()->json([]);
    }

    public function action(string $resource, int $id, string $action, Request $request)
    {
        $error = $this->engine($resource)->action($action, $id, (string) $request->input('input', ''), $request->user()->id);

        return $error ? response()->json(['message' => $error], 422) : response()->json([]);
    }

    private function save(Engine $engine, ?int $id, Request $request)
    {
        try {
            return response()->json(['id' => $engine->save($id, $request)]);
        } catch (AdminError $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function engine(string $resource, ?string $op = null): Engine
    {
        $engine = Engine::for($resource);
        if (!$engine) {
            abort(404, 'Раздел не найден');
        }
        if ($op && !$engine->allows($op)) {
            abort(403, 'Действие недоступно');
        }

        return $engine;
    }
}
