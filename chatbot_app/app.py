from flask import Flask, render_template, request, jsonify
import json
import os

app = Flask(__name__)

# Simple chatbot responses
def get_bot_response(user_message):
    user_message = user_message.lower()
    
    # Define some basic responses
    responses = {
        "hola": "¡Hola! ¿En qué puedo ayudarte hoy?",
        "buenos dias": "¡Buenos días! ¿En qué puedo ayudarte?",
        "buenas tardes": "¡Buenas tardes! ¿En qué puedo ayudarte?",
        "buenas noches": "¡Buenas noches! ¿En qué puedo ayudarte?",
        "como estas": "Estoy bien, gracias por preguntar. ¿Y tú?",
        "adios": "¡Hasta luego! Que tengas un buen día.",
        "gracias": "De nada, ¡estoy para ayudarte!",
        "ayuda": "Puedo ayudarte con información sobre nuestros productos, horarios de atención, o cualquier duda que tengas.",
        "productos": "Ofrecemos una amplia variedad de productos de salud y bienestar. ¿Te gustaría saber más sobre algún producto específico?",
        "contacto": "Puedes contactarnos por email en atencion@saludperfecta.com o por teléfono al +51 987 654 321.",
        "horario": "Nuestro horario de atención es de lunes a viernes de 9:00 a 18:00 horas.",
        "precio": "Los precios de nuestros productos varían según el tipo y marca. ¿Te gustaría información sobre un producto específico?",
        "envio": "Ofrecemos envíos a nivel nacional con tiempos de entrega de 24 a 48 horas hábiles.",
        "devolucion": "Aceptamos devoluciones dentro de los 30 días siguientes a la compra, siempre que el producto esté en condiciones originales."
    }
    
    # Check if the user message matches any predefined responses
    for key in responses:
        if key in user_message:
            return responses[key]
    
    # Default response if no match is found
    return "Lo siento, no entiendo tu mensaje. ¿Podrías reformularlo o preguntarme algo sobre nuestros productos?"

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/chat', methods=['POST'])
def chat():
    user_message = request.json.get('message', '')
    bot_response = get_bot_response(user_message)
    return jsonify({'response': bot_response})

if __name__ == '__main__':
    app.run(debug=True)