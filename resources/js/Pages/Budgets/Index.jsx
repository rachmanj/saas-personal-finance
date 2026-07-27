import { useState, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import { App } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import BudgetList from '../../Components/Budgets/BudgetList';
import BudgetForm from '../../Components/Budgets/BudgetForm';
import BudgetAlert from '../../Components/Budgets/BudgetAlert';

export default function Index() {
    const [modalOpen, setModalOpen] = useState(false);
    const [selectedBudget, setSelectedBudget] = useState(null);
    const [refreshKey, setRefreshKey] = useState(0);

    const handleAdd = useCallback(() => {
        setSelectedBudget(null);
        setModalOpen(true);
    }, []);

    const handleEdit = useCallback((budget) => {
        setSelectedBudget(budget);
        setModalOpen(true);
    }, []);

    const handleFormSuccess = useCallback(() => {
        setModalOpen(false);
        setSelectedBudget(null);
        setRefreshKey((k) => k + 1);
    }, []);

    return (
        <App>
            <AuthenticatedLayout title="Budgets">
                <Head title="Budgets" />

                <BudgetAlert />

                <div style={{ marginTop: 24 }}>
                    <BudgetList
                        refreshKey={refreshKey}
                        onEdit={handleEdit}
                        onAdd={handleAdd}
                    />
                </div>

                <BudgetForm
                    open={modalOpen}
                    onClose={() => {
                        setModalOpen(false);
                        setSelectedBudget(null);
                    }}
                    onSuccess={handleFormSuccess}
                    budget={selectedBudget}
                />
            </AuthenticatedLayout>
        </App>
    );
}
