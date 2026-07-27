import { useEffect, useState } from 'react';
import { Modal, Button } from 'antd';
import { DownloadOutlined } from '@ant-design/icons';
import { getInstallPrompt, clearInstallPrompt } from '../../utils/swRegistration';

export default function InstallPrompt() {
    const [visible, setVisible] = useState(false);
    const [installPrompt, setInstallPrompt] = useState(null);

    useEffect(() => {
        const checkPrompt = () => {
            const prompt = getInstallPrompt();
            if (prompt) {
                setInstallPrompt(prompt);
                setVisible(true);
            }
        };

        window.addEventListener('beforeinstallprompt', checkPrompt);
        checkPrompt();

        return () => window.removeEventListener('beforeinstallprompt', checkPrompt);
    }, []);

    const handleInstall = async () => {
        if (!installPrompt) {
            return;
        }

        installPrompt.prompt();
        await installPrompt.userChoice;
        clearInstallPrompt();
        setVisible(false);
        setInstallPrompt(null);
    };

    return (
        <Modal
            title="Install Personal Finance Tracker"
            open={visible}
            onCancel={() => setVisible(false)}
            footer={[
                <Button key="cancel" onClick={() => setVisible(false)}>
                    Not now
                </Button>,
                <Button key="install" type="primary" icon={<DownloadOutlined />} onClick={handleInstall}>
                    Install App
                </Button>,
            ]}
        >
            <p>Install this app on your device for quick access and offline support.</p>
        </Modal>
    );
}
