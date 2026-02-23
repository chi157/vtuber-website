from flask import Flask, request, jsonify
import requests

app = Flask(__name__)

@app.route('/proxy', methods=['GET'])
def proxy():
    target_url = request.args.get('url')
    if not target_url:
        return jsonify({'error': 'Missing URL parameter'}), 400
    
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36",
        "Accept": "*/*",
        "Referer": "https://vtwebsite.chi157.com/",
    }

    try:
        response = requests.get(target_url, headers=headers)
        response.raise_for_status()
        return response.text, response.status_code
    except requests.exceptions.RequestException as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)