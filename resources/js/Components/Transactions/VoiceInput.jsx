import { Button } from 'antd';
import { AudioOutlined, AudioMutedOutlined } from '@ant-design/icons';
import useMediaRecorder from '../../Hooks/useMediaRecorder';

export default function VoiceInput({ onRecordingComplete }) {
    const { isRecording, error, duration, start, stop } = useMediaRecorder();

    const formatTime = (secs) => {
        const m = Math.floor(secs / 60);
        const s = secs % 60;
        return `${m}:${s.toString().padStart(2, '0')}`;
    };

    const handleClick = async () => {
        if (isRecording) {
            const blob = await stop();
            if (blob) {
                onRecordingComplete(blob);
            }
        } else {
            await start();
        }
    };

    return (
        <div style={{ textAlign: 'center', padding: 16 }}>
            {error && <div style={{ color: 'red', marginBottom: 8 }}>{error}</div>}
            <Button
                type={isRecording ? 'primary' : 'default'}
                danger={isRecording}
                icon={isRecording ? <AudioMutedOutlined /> : <AudioOutlined />}
                onClick={handleClick}
                size="large"
                shape="circle"
                style={{ width: 72, height: 72 }}
            />
            {isRecording && (
                <div style={{ marginTop: 12, fontSize: 18, fontFamily: 'monospace' }}>
                    Recording... {formatTime(duration)}
                </div>
            )}
            {!isRecording && (
                <div style={{ marginTop: 8, color: '#888' }}>Tap to record</div>
            )}
        </div>
    );
}
