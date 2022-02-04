<x-app-layout>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/gh/centrifugal/centrifuge-js@2.8.4/dist/centrifuge.min.js"></script>

    <x-slot name="header"></x-slot>

    <div class="container">
        <form class="chat-create-form row" method="post" action="{{ route('rooms.store') }}">
            @csrf
            <div class="input-group mb-3">
                <input type="text" class="form-control" name="name" placeholder="Type a group name to add">
                <button class="btn btn-outline-secondary" type="submit">{{ __('Add room') }}</button>
            </div>
        </form>

        <div class="row clearfix">
            <div class="col-lg-12">
                <div class="chat-app">
                    <div id="plist" class="room-list">
                        <ul class="list-unstyled chat-list mt-2 mb-0">
                            @foreach($rooms as $room)
                                <li onclick="location.href='{{ route('rooms.show', $room->id) }}'"
                                    id="room-{{ $room->id }}"
                                    class="clearfix {{ !empty($currRoom) && $currRoom->id === $room->id ? 'active' : ''}}">
                                    <i class="fa fa-comments"></i>
                                    <div class="about">
                                        <div class="name">{{ $room->name }}</div>
                                        <span
                                            class="user-name">{{ ($room->messages->count() > 0) ? $room->messages->last()->user->name : '' }}</span>
                                        <span
                                            class="status">{{ ($room->messages->count() > 0) ? Str::limit($room->messages->last()->message, 15) : '' }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="chat">
                        <div class="chat-header clearfix">
                            <div class="row">
                                <div class="col-lg-6">
                                    @if (!empty($currRoom))
                                        <i class="fa fa-comments"></i>
                                        <div class="chat-about">
                                            <h6 class="m-b-0">Room: {{ $currRoom->name }}</h6>
                                            <small>Num participants: {{ $currRoom->users->count() }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="chat-history" id="chat-history">
                            @if (!empty($currRoom))
                                <ul class="m-b-0">
                                    @foreach($currRoom->messages as $message)
                                        <li class="clearfix">
                                            @if ($message->sender_id === Auth::user()->id)
                                                <div class="message-data text-right">
                                        <span class="message-data-time">
                                            {{ $message->created_at->toFormattedDateString() }}, {{ $message->created_at->toTimeString() }}
                                        </span>
                                                </div>
                                                <div
                                                    class="message my-message float-right">{{ $message->message }}</div>
                                            @else
                                                <div class="message-data text-left">
                                                    <img src="https://robohash.org/{{ $message->user->name }}"
                                                         alt="avatar">
                                                    <span class="message-data-time">
                                            <b>{{ $message->user->name }}</b>, {{ $message->created_at->toFormattedDateString() }}, {{ $message->created_at->toTimeString() }}
                                        </span>
                                                </div>
                                                <div class="message other-message float-left">
                                                    {{ $message->message }}
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    This is an example chat application with Laravel and Centrifugo.
                                    Login with different accounts, create new rooms, publish messages into rooms
                                    and enjoy an instant communication.
                                </div>
                            @endif
                        </div>

                        @if (!empty($currRoom))
                            @if ($isJoin)
                                <div class="chat-message clearfix">
                                    <div class="form-group">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text" id="basic-addon1"><i class="fa fa-send"></i></span>
                                            <input type="text" id="chat-message-input" class="form-control"
                                                   placeholder="Enter text here...">
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="chat-message clearfix">
                                    <form class="inline-block" method="post" style="text-align: center;"
                                          action="{{ route('rooms.join', $currRoom->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">Join this room</button>
                                    </form>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // helper functions to work with escaping html.
        const tagsToReplace = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;'
        };

        function replaceTag(tag) {
            return tagsToReplace[tag] || tag;
        }

        function safeTagsReplace(str) {
            return str.replace(/[&<>]/g, replaceTag);
        }

        window.addEventListener('load', () => {
            initApp();
        })

        function initApp() {
            const currentUserId = "{{ Auth::user() -> id }}";
            const currentRoomId = "{{ !empty($currRoom) ? $currRoom -> id : 0 }}";

            const chatHistory = document.querySelector('#chat-history');
            const messageInput = document.querySelector('#chat-message-input');

            function scrollToLastMessage() {
                chatHistory.scrollTop = chatHistory.scrollHeight;
            }

            scrollToLastMessage();

            if (messageInput !== null) {
                messageInput.focus();

                const csrfToken = "{{ csrf_token() }}";
                messageInput.onkeyup = function (e) {
                    if (e.keyCode === 13) { // enter, return
                        e.preventDefault();
                        const message = messageInput.value;
                        if (!message) {
                            return;
                        }
                        const xhttp = new XMLHttpRequest();
                        xhttp.open("POST", "/rooms/" + currentRoomId + "/publish");
                        xhttp.setRequestHeader("X-CSRF-TOKEN", csrfToken);
                        xhttp.send(JSON.stringify({
                            message: message
                        }));
                        messageInput.value = '';
                    }
                };
            }

            function addMessage(data) {
                const chatThreads = document.querySelector('#chat-history ul');
                const senderName = safeTagsReplace(data.senderName);
                const text = safeTagsReplace(data.text);
                const date = data.createdAtFormatted;
                const isSelf = data.senderId.toString() === currentUserId;

                let msg = '<div class="message-data text-left">' +
                    '<img src="https://robohash.org/' + senderName + '" alt="avatar">' +
                    '<span class="message-data-time"><b>' + senderName + '</b>, ' + date + '</span>' +
                    '</div>' +
                    '<div class="message other-message float-left">' + text + '</div>'

                if (isSelf) {
                    msg = '<div class="message-data text-right">' +
                        '<span class="message-data-time">' + date + '</span>' +
                        '</div>' +
                        '<div class="message my-message float-right">' + text + '</div>'
                }

                const chatNewThread = document.createElement('li');
                chatNewThread.className = "clearfix";
                chatNewThread.innerHTML = msg;
                chatThreads.appendChild(chatNewThread);
            }

            function addRoomLastMessage(data) {
                const lastRoomMessageText = document.querySelector('#room-' + data.roomId + ' .status');
                const lastRoomMessageUserName = document.querySelector('#room-' + data.roomId + ' .user-name');
                let text = data.text.substr(0, 15);
                if (data.text.length > 15) {
                    text += "..."
                }
                lastRoomMessageText.innerHTML = safeTagsReplace(text);
                lastRoomMessageUserName.innerHTML = safeTagsReplace(data.senderName);
            }

            console.log(window.location.host);

            const centrifuge = new Centrifuge("ws://" + window.location.host + "/connection/websocket");

            centrifuge.on('connect', function (ctx) {
                console.log("connected", ctx);
            });

            centrifuge.on('disconnect', function (ctx) {
                console.log("disconnected", ctx);
            });

            centrifuge.on('publish', function (ctx) {
                if (ctx.data.roomId.toString() === currentRoomId) {
                    addMessage(ctx.data);
                    scrollToLastMessage();
                }
                addRoomLastMessage(ctx.data);
            });

            centrifuge.connect();
        }
    </script>
</x-app-layout>
