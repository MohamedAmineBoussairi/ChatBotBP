import { useState, useRef, useEffect } from 'react'
import { Send, Landmark, User, Loader2 } from 'lucide-react'

function App() {
  const [messages, setMessages] = useState([
    { id: 1, type: 'bot', text: 'Bonjour ! Je suis l\'assistant virtuel de la Banque Populaire. Comment puis-je vous aider aujourd\'hui ?\nN\'hésitez pas à me poser vos questions concernant Chaabi Net ou Chaabi Mobile.' }
  ])
  const [input, setInput] = useState('')
  const [loading, setLoading] = useState(false)
  const messagesEndRef = useRef(null)

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }

  useEffect(() => {
    scrollToBottom()
  }, [messages])

  const handleSend = async (e) => {
    e.preventDefault()
    if (!input.trim()) return

    const userMessage = { id: Date.now(), type: 'user', text: input.trim() }
    setMessages(prev => [...prev, userMessage])
    setInput('')
    setLoading(true)

    try {
      const apiUrl = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
        ? 'http://127.0.0.1:8000/api/chat' 
        : 'https://chatbotbp.onrender.com/api/chat';

      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ message: userMessage.text })
      })
      
      const data = await response.json()
      
      setMessages(prev => [...prev, {
        id: Date.now() + 1,
        type: 'bot',
        text: data.reply || 'Désolé, je n\'ai pas de réponse.'
      }])
    } catch (error) {
      setMessages(prev => [...prev, {
        id: Date.now() + 1,
        type: 'bot',
        text: 'Désolé, impossible de joindre le serveur. Veuillez vérifier que le serveur Laravel est en cours d\'exécution (php artisan serve).'
      }])
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4 font-sans">
      <div className="w-full max-w-3xl bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col border border-slate-200/60 h-[85vh]">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-bp-brown to-bp-brown-light p-6 text-white shrink-0 flex items-center shadow-lg relative z-10">
          <div className="w-14 h-14 bg-white rounded-2xl flex items-center justify-center mr-5 shadow-inner rotate-3 transition-transform hover:rotate-6">
            <Landmark className="text-bp-orange" size={32} />
          </div>
          <div>
            <h1 className="text-2xl font-extrabold tracking-tight">ChatBot BP</h1>
            <p className="text-bp-orange-light text-sm font-semibold opacity-90 tracking-wide uppercase mt-1 text-[11px]">Chatbot Propulsé par AI</p>
          </div>
        </div>

        {/* Chat Area */}
        <div className="flex-1 overflow-y-auto p-6 lg:p-8 space-y-8 bg-[#fafafa]">
          {messages.map((msg) => (
            <div key={msg.id} className={`flex ${msg.type === 'user' ? 'justify-end' : 'justify-start'} animate-in fade-in slide-in-from-bottom-2 duration-300`}>
              <div className={`flex gap-4 max-w-[85%] ${msg.type === 'user' ? 'flex-row-reverse' : 'flex-row'}`}>
                <div className={`w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-sm ${msg.type === 'user' ? 'bg-gradient-to-br from-bp-orange to-[#d66e28] text-white rotate-[-2deg]' : 'bg-white border border-slate-200 text-bp-brown rotate-2'}`}>
                  {msg.type === 'user' ? <User size={20} /> : <Landmark size={20} />}
                </div>
                <div className={`p-5 rounded-3xl shadow-sm ${msg.type === 'user' ? 'bg-gradient-to-br from-bp-orange to-[#e5772e] text-white rounded-tr-sm' : 'bg-white border border-slate-100 text-slate-700 rounded-tl-sm shadow-md shadow-slate-200/40'}`}>
                  <p className="whitespace-pre-wrap leading-relaxed text-[15px]">{msg.text}</p>
                </div>
              </div>
            </div>
          ))}
          {loading && (
            <div className="flex justify-start animate-in fade-in duration-300">
              <div className="flex gap-4 max-w-[85%]">
                <div className="w-10 h-10 rounded-2xl bg-white border border-slate-200 text-bp-brown flex items-center justify-center shrink-0 shadow-sm rotate-2">
                  <Landmark size={20} />
                </div>
                <div className="py-4 px-6 rounded-3xl bg-white border border-slate-100 text-slate-500 rounded-tl-sm shadow-md shadow-slate-200/40 flex items-center gap-3">
                  <Loader2 className="animate-spin text-bp-orange" size={20} />
                  <span className="text-sm font-medium">Recherche dans la FAQ...</span>
                </div>
              </div>
            </div>
          )}
          <div ref={messagesEndRef} />
        </div>

        {/* Input Area */}
        <div className="p-4 bg-white border-t border-slate-100 shrink-0 shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.05)] z-20">
          <form onSubmit={handleSend} className="flex gap-3 items-end max-w-4xl mx-auto">
            <div className="flex-1 bg-[#f8f9fa] border border-slate-200 hover:border-slate-300 rounded-3xl focus-within:ring-4 focus-within:ring-bp-orange/20 focus-within:border-bp-orange transition-all p-1.5 duration-200">
              <textarea
                value={input}
                onChange={(e) => setInput(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    handleSend(e);
                  }
                }}
                placeholder="Ex: Quels sont les avantages de Chaabi Net ?"
                className="w-full bg-transparent border-none focus:ring-0 resize-none max-h-32 min-h-[44px] px-4 py-2 text-slate-700 placeholder:text-slate-400/80 text-[15px] outline-none"
                rows="1"
              />
            </div>
            <button
              type="submit"
              disabled={!input.trim() || loading}
              className="bg-bp-orange hover:bg-[#d66e28] text-white h-[56px] w-[56px] flex items-center justify-center rounded-2xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-bp-orange/30 disabled:shadow-none hover:scale-105 active:scale-95 group"
            >
              <Send size={24} className={(input.trim() && !loading) ? 'group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform duration-300' : ''} />
            </button>
          </form>
          <div className="text-center mt-3">
            <p className="text-[11px] text-slate-400 font-medium">L'assistant utilise une Intelligence Artificielle et s'appuie sur la base de connaissances officielle (FAQ BP). Il peut parfois faire des erreurs.</p>
          </div>
        </div>
      </div>
    </div>
  )
}

export default App
