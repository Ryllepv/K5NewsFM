# K5 News Radio 88.7 FM - Stream Setup Instructions

## 🎯 How to Connect Your Real Radio Stream

The website is now ready to play your actual 88.7 FM radio stream! Follow these steps to connect it:

### 📡 Step 1: Get Your Stream URL

You need to obtain the direct URL to your radio station's live stream. This could be from:

1. **Your Radio Station Software** (like SAM Broadcaster, RadioDJ, etc.)
2. **Streaming Service Provider** (like Shoutcast, Icecast, Zeno.FM, etc.)
3. **Your Radio Equipment** (if you have an encoder/streaming device)

Common stream URL formats:
- `http://your-server.com:8000/stream`
- `https://stream.zeno.fm/your-station-id`
- `http://ice1.somafm.com/groovesalad-256-mp3` (example format)

### 🔧 Step 2: Update the JavaScript File

1. Open `js/k5-news-radio.js`
2. Find line 20 that says:
   ```javascript
   this.streamUrl = 'https://your-radio-stream-url.com/stream';
   ```
3. Replace it with your actual stream URL:
   ```javascript
   this.streamUrl = 'http://your-actual-stream-url.com:8000/stream';
   ```

### 🎵 Step 3: Test the Stream

1. **Test Button**: Use the "Test Stream" button to test with a demo stream first
2. **Live Button**: Click "Listen Live Now" to test your actual stream
3. **Check Browser Console**: Open Developer Tools (F12) to see any error messages

### 🔍 Common Stream URL Examples

#### Shoutcast/Icecast Servers:
```
http://your-server.com:8000/stream
http://your-server.com:8000/live
http://your-server.com:8000/radio.mp3
```

#### Zeno.FM:
```
https://stream.zeno.fm/your-station-id
```

#### Radio.co:
```
https://streaming.radio.co/your-station-id/listen
```

#### Live365:
```
http://ice1.live365.com/live365-your-station
```

### 🛠️ Troubleshooting

#### Stream Won't Play:
1. **Check URL**: Make sure the stream URL is correct
2. **CORS Issues**: Your stream server might need CORS headers
3. **HTTPS/HTTP**: If your website is HTTPS, your stream should be HTTPS too
4. **Format**: Ensure stream is in MP3 or AAC format
5. **Firewall**: Check if your server's firewall allows the stream port

#### Browser Console Errors:
- Open Developer Tools (F12) → Console tab
- Look for error messages when clicking play
- Common errors and solutions:

```
CORS Error: Add these headers to your stream server:
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, OPTIONS
Access-Control-Allow-Headers: Content-Type
```

```
Network Error: Check if stream URL is accessible:
- Try opening the stream URL directly in browser
- Check if server is running
- Verify port is open
```

### 📞 Getting Help

If you need help finding your stream URL:

1. **Contact your radio software provider**
2. **Check your streaming service dashboard**
3. **Ask your IT administrator**
4. **Contact your radio equipment manufacturer**

### 🎯 For K5 News Radio 88.7 FM Specifically:

Since you're broadcasting on 88.7 FM in Olongapo, you likely have:
1. **FM Transmitter** → **Audio Encoder** → **Streaming Server**
2. The streaming server should provide you with a URL
3. This URL is what you need to put in the JavaScript file

### 📝 Quick Setup Checklist:

- [ ] Obtain your actual stream URL
- [ ] Update `js/k5-news-radio.js` line 20
- [ ] Test with "Test Stream" button first
- [ ] Test with "Listen Live Now" button
- [ ] Check browser console for errors
- [ ] Verify stream works on different devices
- [ ] Test with different browsers

### 🚀 Once Working:

Your website will be able to:
- ✅ Play your live 88.7 FM stream
- ✅ Show real-time audio visualizer
- ✅ Control volume
- ✅ Display connection status
- ✅ Handle stream interruptions gracefully
- ✅ Work on mobile devices

---

**Need immediate testing?** 
Use the "Test Stream" button which will play a demo stream for 30 seconds to verify the audio player is working correctly.
