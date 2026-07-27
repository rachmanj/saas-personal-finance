import { useRef, useState, useCallback } from 'react';

export default function useCamera() {
    const videoRef = useRef(null);
    const streamRef = useRef(null);
    const [isActive, setIsActive] = useState(false);
    const [error, setError] = useState(null);

    const start = useCallback(async () => {
        try {
            setError(null);
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' },
            });
            streamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
            }
            setIsActive(true);
        } catch (err) {
            setError(err.message || 'Camera access denied');
        }
    }, []);

    const stop = useCallback(() => {
        if (streamRef.current) {
            streamRef.current.getTracks().forEach((track) => track.stop());
            streamRef.current = null;
        }
        setIsActive(false);
    }, []);

    const capture = useCallback(() => {
        if (!videoRef.current) return null;
        const canvas = document.createElement('canvas');
        canvas.width = videoRef.current.videoWidth;
        canvas.height = videoRef.current.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(videoRef.current, 0, 0);
        return canvas.toDataURL('image/jpeg', 0.8);
    }, []);

    return { videoRef, isActive, error, start, stop, capture };
}
