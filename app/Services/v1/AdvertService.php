<?php

namespace App\Services\v1;

use App\Events\ChatCreatedEvent;
use App\Models\Advert;
use App\Presenters\v1\AdvertPresenter;
use App\Repositories\AdvertRepo;
use App\Services\BaseService;
use Illuminate\Support\Facades\Storage;

class AdvertService extends BaseService
{
    private AdvertRepo $advertRepo;

    public function __construct() {
        $this->advertRepo = new AdvertRepo();
    }

    public function index(array $params)
    {
        return $this->resultCollections($this->advertRepo->index($params), AdvertPresenter::class, 'list');
    }

    public function info($id)
    {
        $advert = $this->advertRepo->info($id);
        
        if (is_null($advert)) {
            return $this->errNotFound(__('advert.not_found'));
        }

        return $this->result([
            'advert' => (new AdvertPresenter($advert))->info(),
        ]);
    }

    public function create(array $data)
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errFobidden('Unathorized');
        }

        if ($data['type'] == Advert::TYPE_SERVICE) {
            $checkExecutor = (new ExecutorService())->checkExecutor($user);
            if (!$this->isSuccess($checkExecutor)) {
                return $checkExecutor;
            }
        }

        $data['user_id'] = $user->id;

        $advert = $this->advertRepo->store($data);

        if (isset($data['files'])) {
            foreach($data['files'] as $file) {
                $path = $file->store('public/advert/');
                $advert->media()->create([
                    'storage_link' => Storage::url($path), 
                ]);
            }
        }

        return $this->ok(__('advert.created'));
    }

    public function update($id, array $data)
    {
        $advert = Advert::find($id);

        if (is_null($advert)) {
            return $this->errNotFound(__('advert.not_found'));
        }

        $user = auth('api')->user();

        if ($advert->user_id != $user->id) {
            return $this->error(403, __('advert.cannot_edit_foreign'));
        }
        if (isset($data['type']) && $data['type'] == Advert::TYPE_SERVICE) {
            $checkExecutor = (new ExecutorService())->checkExecutor($user);
            if (!$this->isSuccess($checkExecutor)) {
                return $checkExecutor;
            }
        }
        $advert->media()->delete();

        if (isset($data['files'])) {
            foreach($data['files'] as $file) {
                $path = $file->store('public/advert/');
                $advert->media()->create([
                    'storage_link' => Storage::url($path), 
                ]);
            }
        }
        unset($data['files']);

        $this->advertRepo->update($advert->id, $data);

        return $this->ok(__('advert.saved'));
    }

    public function delete($id)
    {
        $advert = Advert::find($id);

        if (is_null($advert)) {
            return $this->errNotFound(__('advert.not_found'));
        }

        $advert->media()->delete();
        $advert->delete();

        return $this->ok(__('advert.deleted'));
    }

    public function createChat(int $advertId)
    {
        $advert = Advert::find($advertId);
        if (is_null($advert)) {
            return $this->errNotFound(__('advert.not_found'));
        }

        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errFobidden(__('advert.auth_error'));
        }

        $chat = $advert->chatable()->create([]);
        $chat->members()->attach([$user->id => ['chat_id' => $chat->id], $advert->user_id => ['chat_id' => $chat->id]]);

        event(new ChatCreatedEvent($advert->user_id, $chat));

        return $this->ok();
    }
}