import { useState } from 'react';
import { Button, Space } from 'antd';
import { CameraOutlined, ReloadOutlined, CheckOutlined } from '@ant-design/icons';
import useCamera from '../../Hooks/useCamera';

export default function ReceiptCapture({ onCapture }) {
    const { videoRef, isActive, error, start, stop, capture } = useCamera();
    const [photo, setPhoto] = useState(null);

    const handleCapture = () => {
        const dataUrl = capture();
        if (dataUrl) {
            setPhoto(dataUrl);
            stop();
        }
    };

    const handleRetake = () => {
        setPhoto(null);
        start();
    };

    const handleConfirm = () => {
        if (photo) {
            onCapture(photo);
        }
    };

    return (
        <div style={{ textAlign: 'center' }}>
            {error && <p style={{ color: 'red', marginBottom: 8 }}>{error}</p>}
            {!isActive && !photo && (
                <Button icon={<CameraOutlined />} onClick={start} type="primary" block>
                    Open Camera
                </Button>
            )}
            {isActive && !photo && (
                <div>
                    <video ref={videoRef} autoPlay playsInline style={{ width: '100%', maxHeight: 300, borderRadius: 8 }} />
                    <Space style={{ marginTop: 12 }}>
                        <Button icon={<CameraOutlined />} onClick={handleCapture}>Capture</Button>
                        <Button onClick={stop}>Cancel</Button>
                    </Space>
                </div>
            )}
            {photo && (
                <div>
                    <img src={photo} alt="Receipt" style={{ width: '100%', maxHeight: 300, borderRadius: 8, objectFit: 'contain' }} />
                    <Space style={{ marginTop: 12 }}>
                        <Button icon={<ReloadOutlined />} onClick={handleRetake}>Retake</Button>
                        <Button icon={<CheckOutlined />} type="primary" onClick={handleConfirm}>Use Photo</Button>
                    </Space>
                </div>
            )}
        </div>
    );
}
