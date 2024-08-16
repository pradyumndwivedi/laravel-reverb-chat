<?php

namespace App\Livewire;

use App\Events\MessageSendEvent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatComponent extends Component
{
    public $user;
    public $sender_id;
    public $receiver_id;
    public $message = '';
    public $messages = [];



    public function render()
    {
        return view('livewire.chat-component');
    }

    public function mount($user_id)
    {
        $this->sender_id = Auth::user()->id;
        $this->receiver_id = $user_id;

        $messages = Message::where([['sender_id', $this->sender_id], ['receiver_id', $this->receiver_id]])
            ->orWhere([['sender_id', $this->receiver_id], ['receiver_id', $this->sender_id]])
            ->with('sender:id,name', 'receiver:id,name')->get();

        foreach ($messages as $message) {
            $this->appendMessage($message);
        }

        // dd($this->messages);
        $this->user = User::whereId($user_id)->first();
    }

    public function sendMessage()
    {
        $chatMessage = new Message();
        $chatMessage->sender_id = $this->sender_id;
        $chatMessage->receiver_id = $this->receiver_id;
        $chatMessage->message = $this->message;
        $chatMessage->save();
        // $this->emit('', $chatMessage->id);

        $this->appendMessage($chatMessage);

        broadcast(new MessageSendEvent($chatMessage))->toOthers();

        $this->message = '';
    }

    #[On('echo-private:chat-channel.{sender_id},MessageSendEvent')]
    public function listenForMessage($event){
       $chatMessage = Message::whereId($event['message']['id'])
       ->with('sender:id,name', 'receiver:id,name')->first();
       $this->appendMessage($chatMessage);
    }

    public function appendMessage($message){
        $this->messages[] = [
            'id' => $message->id,
            'message' => $message->message,
            'sender' => $message->sender->name,
            'receiver' => $message->receiver->name,
        ];
    }
}
