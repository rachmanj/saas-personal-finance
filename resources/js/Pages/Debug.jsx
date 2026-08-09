import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';

export default function Debug() {
    const { props, component, url, version } = usePage();
    
    return (
        <AuthenticatedLayout title="Debug">
            <Head title="Debug" />
            <div style={{ padding: 20, color: '#fff' }}>
                <h2>Inertia Debug</h2>
                <pre style={{ background: '#1a1a1a', padding: 16, borderRadius: 8, overflow: 'auto', fontSize: 13 }}>
                    {JSON.stringify({ component, url, version, props }, null, 2)}
                </pre>
            </div>
        </AuthenticatedLayout>
    );
}
