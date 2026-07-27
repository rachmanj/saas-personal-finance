import { Head } from '@inertiajs/react';
import { App, Card } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import ReportExport from '../../Components/Reports/ReportExport';
import OfflineIndicator from '../../Components/PWA/OfflineIndicator';

export default function Index() {
    return (
        <App>
            <AuthenticatedLayout title="Reports & Export">
                <Head title="Reports & Export" />
                <OfflineIndicator />

                <Card title="Export Transactions">
                    <p style={{ marginBottom: 16 }}>
                        Export your transaction data in various formats for reporting, backup, or import into other tools.
                    </p>
                    <ReportExport />
                </Card>
            </AuthenticatedLayout>
        </App>
    );
}
