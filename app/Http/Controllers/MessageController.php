<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\NewMessage;
use App\Models\Message;
use App\Models\Admin;
use App\Models\Pekerja;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required',
            'sender_id' => 'required',
            'sender_type' => 'required|in:Admin,Pekerja',
            'receiver_id' => 'required',
            'receiver_type' => 'required|in:Admin,Pekerja',
        ]);

        // Determine the sender and receiver models based on type
        $senderModel = ($request->input('sender_type') == 'Admin') ? Admin::class : Pekerja::class;
        $receiverModel = ($request->input('receiver_type') == 'Admin') ? Admin::class : Pekerja::class;

        // Find the sender and receiver models by ID
        $sender = $senderModel::find($request->input('sender_id'));
        $receiver = $receiverModel::find($request->input('receiver_id'));

        // Create a new message
        $message = new Message($request->all());

        // Associate the sender and receiver with the message
        $message->sender()->associate($sender);
        $message->receiver()->associate($receiver);

        $message->save();

        // Broadcast the NewMessage event
        broadcast(new NewMessage($message));

        return response()->json($message, 201);
    }
}
