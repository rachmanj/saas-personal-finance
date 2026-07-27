import { useRef, useState, useCallback } from 'react';

export default function useMediaRecorder() {
    const mediaRecorderRef = useRef(null);
    const chunksRef = useRef([]);
    const [isRecording, setIsRecording] = useState(false);
    const [error, setError] = useState(null);
    const [duration, setDuration] = useState(0);
    const timerRef = useRef(null);

    const start = useCallback(async () => {
        try {
            setError(null);
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const recorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
            mediaRecorderRef.current = recorder;
            chunksRef.current = [];

            recorder.ondataavailable = (e) => {
                if (e.data.size > 0) chunksRef.current.push(e.data);
            };

            recorder.start();
            setIsRecording(true);
            setDuration(0);
            timerRef.current = setInterval(() => setDuration((d) => d + 1), 1000);
        } catch (err) {
            setError(err.message || 'Microphone access denied');
        }
    }, []);

    const stop = useCallback(() => {
        return new Promise((resolve) => {
            if (!mediaRecorderRef.current) {
                resolve(null);
                return;
            }
            mediaRecorderRef.current.onstop = () => {
                const blob = new Blob(chunksRef.current, { type: 'audio/webm' });
                mediaRecorderRef.current.stream.getTracks().forEach((t) => t.stop());
                resolve(blob);
            };
            mediaRecorderRef.current.stop();
            clearInterval(timerRef.current);
            setIsRecording(false);
        });
    }, []);

    return { isRecording, error, duration, start, stop };
}
