import { Alert } from 'antd';
import { WifiOutlined } from '@ant-design/icons';
import useOfflineDetection from '../../Hooks/useOfflineDetection';

export default function OfflineIndicator() {
    const { isOnline } = useOfflineDetection();

    if (isOnline) {
        return null;
    }

    return (
        <Alert
            message="You are currently offline"
            description="Some features may be unavailable until your connection is restored."
            type="warning"
            showIcon
            icon={<WifiOutlined />}
            style={{ marginBottom: 16 }}
            banner
        />
    );
}
