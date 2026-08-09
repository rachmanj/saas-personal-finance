import { Head, usePage } from '@inertiajs/react';
import { App, Card, Col, Row, Typography } from 'antd';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';
import SummaryCards from '../Components/Dashboard/SummaryCards';
import RecentTransactionsWidget from '../Components/Dashboard/RecentTransactionsWidget';
import UpcomingWidget from '../Components/Dashboard/UpcomingWidget';
import GoalProgressWidget from '../Components/Dashboard/GoalProgressWidget';
import BudgetAlertWidget from '../Components/Dashboard/BudgetAlertWidget';

const { Title } = Typography;

export default function Dashboard() {
    const { props } = usePage();
    const dashboard = props.dashboard || {};

    const {
        income_total = 0,
        expense_total = 0,
        net_worth = 0,
        last_10_transactions = [],
        saving_goals = [],
    } = dashboard;

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
                            <UpcomingWidget />
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
                            <BudgetAlertWidget />
                        </Card>
                    </Col>
                </Row>
            </AuthenticatedLayout>
        </App>
    );
}
