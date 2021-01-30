using System;
using System.Reactive.Linq;
using System.Reactive.Subjects;
using System.Reactive.Threading.Tasks;
using System.Threading.Tasks;
using Google.Protobuf.WellKnownTypes;
using Grpc.Core;
using Microsoft.Extensions.Logging;

namespace GrpcWebChat.Services
{
    public class ChatService : Chat.ChatBase
    {
        private readonly ILogger<ChatService> _logger;

        private readonly Subject<ChatMessage> _chatMessageSubject;
        
        public ChatService(ILogger<ChatService> logger)
        {
            _logger = logger;
            _chatMessageSubject = new Subject<ChatMessage>();
        }

        public override Task<Empty> SendMessage(SendMessageRequest request, ServerCallContext context)
        {
            var chatMessage = new ChatMessage
            {
                Body = request.Body,
                Name = request.Name,
                Date = new Timestamp { Seconds = DateTimeOffset.Now.ToUnixTimeSeconds() }
            };
            _chatMessageSubject.OnNext(chatMessage);
            return Task.FromResult(new Empty());
        }

        public override Task Subscribe(
            Empty request,
            IServerStreamWriter<ChatMessage> responseStream,
            ServerCallContext context
        ) {
            return _chatMessageSubject.Do(chatMessage =>
            {
                responseStream.WriteAsync(chatMessage);
            }).ToTask();
        }
    }
}
