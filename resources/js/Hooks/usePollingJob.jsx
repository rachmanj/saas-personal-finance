import { useState, useEffect, useCallback, useRef } from 'react';
import { apiGet } from '../utils/api';

export default function usePollingJob(endpoint, { interval = 2000, maxAttempts = 30, enabled = false } = {}) {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const attemptsRef = useRef(0);
    const timerRef = useRef(null);

    const poll = useCallback(async () => {
        try {
            const result = await apiGet(endpoint);
            setData(result.data);
            attemptsRef.current++;

            if (result.data?.status === 'completed' || result.data?.status === 'failed') {
                setLoading(false);
                return;
            }

            if (attemptsRef.current >= maxAttempts) {
                setError(new Error('Polling timed out'));
                setLoading(false);
                return;
            }

            timerRef.current = setTimeout(poll, interval);
        } catch (err) {
            setError(err);
            setLoading(false);
        }
    }, [endpoint, interval, maxAttempts]);

    const startPolling = useCallback(() => {
        setLoading(true);
        setError(null);
        attemptsRef.current = 0;
        poll();
    }, [poll]);

    useEffect(() => {
        if (enabled) {
            startPolling();
        }
        return () => clearTimeout(timerRef.current);
    }, [enabled, startPolling]);

    return { data, loading, error, startPolling };
}
