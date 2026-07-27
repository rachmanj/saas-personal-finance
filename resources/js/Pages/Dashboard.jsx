import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { App, Card, Col, Row, Spin, Typography, Alert } from 'antd';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';
import SummaryCards from '../Components/Dashboard/SummaryCards';
import RecentTransactionsWidget from '../Components/Dashboard/RecentTransactionsWidget';
import UpcomingWidget from '../Components/Dashboard/UpcomingWidget';
import GoalProgressWidget from '../Components/Dashboard/GoalProgressWidget';
import BudgetAlertWidget from '../Components/Dashboard/BudgetAlertWidget';
import { apiGet } from '../utils/api';

const { Title } = Typography;

export default function Dashboard() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        apiGet('/api/dashboard/summary')
            .then((res) => {
                setData(res.data);
                setLoading(false);
            })
            .catch((err) => {
                setError(err.message || 'Failed to load dashboard');
                setLoading(false);
            });
    }, []);

    if (loading) {
        return (
            <AuthenticatedLayout title="Dashboard">
                <Head title="Dashboard" />
                <div style={{ textAlign: 'center', padding: 48 }}>
                    <Spin size="large" />
                </div>
            </AuthenticatedLayout>
        );
    }

    if (error) {
        return (
            <AuthenticatedLayout title="Dashboard">
                <Head title="Dashboard" />
                <Alert message="Error" description={error} type="error" showIcon />
            </AuthenticatedLayout>
        );
    }

    const {
        income_total = 0,
        expense_total = 0,
        net_worth = 0,
        last_10_transactions = [],
        budgets = [],
        upcoming_recurring = [],
        saving_goals = [],
    } = data || {};

    return (
        <App>
            <AuthenticatedLayout title="Dashboard">
                <Head title="Dashboard" />

                <SummaryCards
                    incomeTotal={income_total}
                    expenseTotal={expense_total}
                    netWorth={net_worth}
                />

                <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                    <Col xs={24} lg={12}>
                        <Card title="Recent Transactions">
                            <RecentTransactionsWidget transactions={last_10_transactions} />
                        </Card>
                    </Col>
                    <Col xs={24} lg={12}>
                        <Card title="Upcoming">
                            <UpcomingWidget upcoming={upcoming_recurring} />
                        </Card>
                    </Col>
                </Row>

                <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                    <Col xs={24} lg={12}>
                        <Card title="Saving Goals">
                            <GoalProgressWidget goals={saving_goals} />
                        </Card>
                    </Col>
                    <Col xs={24} lg={12}>
                        <Card title="Budget Alerts">
                            <BudgetAlertWidget budgets={budgets} />
                        </Card>
                    </Col>
                </Row>
            </AuthenticatedLayout>
        </App>
    );
}