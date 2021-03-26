import { SendMessageRequest, ChatMessage } from './pb-web/chat_pb'
import { ChatClient } from './pb-web/ChatServiceClientPb'
import { ClientReadableStream, Error, Status } from 'grpc-web'
import { Empty } from 'google-protobuf/google/protobuf/empty_pb'

type Observer<T> = (subject: T) => void;
type Model = {
  readonly chatMessages: ChatMessage[],
  addChatMessage (chatMessage: ChatMessage): void
  addObserver (observer: Observer<Model>): void
}

const initialModel = (): Model => {
  const observers: Observer<Model>[] = []
  const chatMessages: ChatMessage[] = []
  const notify = (model: Model) => {
    observers.forEach((observer) => {
      observer(model)
    })
  }

  return {
    get chatMessages () {
      return chatMessages
    },
    addChatMessage (chatMessage: ChatMessage) {
      chatMessages.push(chatMessage)
      notify(this)
    },
    addObserver (observer: Observer<Model>) {
      observers.push(observer)
    }
  }
}
const model = initialModel()
const server = new URLSearchParams(location.search).get('server');
const serverUrl = server == 'csharp'
  ? 'https://localhost:5001'
  : 'https://localhost:9000';

const chatClient = new ChatClient(serverUrl)

window.onload = () => {
  const $views = {
    nameInput: document.getElementById('name-input') as HTMLInputElement,
    bodyInput: document.getElementById('body-input') as HTMLInputElement,
    output: document.getElementById('output') as HTMLElement,
    sendButton: document.getElementById('send-button') as HTMLButtonElement
  }
  if (!$views.nameInput) {
    throw new Error('name input element not found')
  }
  if (!$views.bodyInput) {
    throw new Error('body input element not found')
  }
  if (!$views.output) {
    throw new Error('output element not found')
  }
  if (!$views.sendButton) {
    throw new Error('button element not found')
  }

  // チャットメッセージ描画の設定
  const render = (model: Model) => {
    $views.output.innerHTML = ''
    model.chatMessages.forEach((chatMessage) => {
      const date = chatMessage.getDate()?.toDate()
      const chatMessageElement = document.createElement('p')
      chatMessageElement.innerHTML = `[${date?.toISOString()}] ${chatMessage.getName()}: ${chatMessage.getBody()} `
      $views.output.append(chatMessageElement)
    })
  }
  model.addObserver(render)

  // チャットメッセージ送信イベントの処理を設定
  $views.sendButton.addEventListener('click', () => {
    const name = $views.nameInput.value
    if (!name) {
      return
    }
    const body = $views.bodyInput.value
    if (!body) {
      return
    }
    const request = new SendMessageRequest()
    request.setBody(body)
    request.setName(name)
    chatClient.sendMessage(request, {}, (err: Error) => {
      if (err) {
        console.error(err)
      }
    })
    $views.bodyInput.value = ''
  })

  // チャットメッセージ購読を設定
  const chatMessageStream = chatClient.subscribe(new Empty()) as ClientReadableStream<ChatMessage>
  chatMessageStream.on('data', (chatMessage: ChatMessage) => {
    console.log('data', chatMessage)
    model.addChatMessage(chatMessage)
  })
  chatMessageStream.on('status', (status: Status) => {
    console.log('status', status)
  })
  chatMessageStream.on('end', () => {
    console.log('stream end', new Date())
  })
}
