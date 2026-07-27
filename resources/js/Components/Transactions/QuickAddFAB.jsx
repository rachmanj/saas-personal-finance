import { useState } from 'react';
import { FloatButton } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import QuickAddModal from './QuickAddModal';

/**
 * @param {{ onSuccess?: () => void }} props
 */
export default function QuickAddFAB({ onSuccess }) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <FloatButton
                type="primary"
                icon={<PlusOutlined />}
                onClick={() => setOpen(true)}
                style={{ bottom: 24, right: 24 }}
                tooltip="Quick Add Transaction"
            />
            <QuickAddModal
                open={open}
                onClose={() => setOpen(false)}
                onSuccess={() => {
                    setOpen(false);
                    onSuccess?.();
                }}
            />
        </>
    );
}
