import { Head, usePage } from '@inertiajs/react';
import { App, Card, Col, Row, Typography, theme } from 'antd';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';
import SummaryCards from '../Components/Dashboard/SummaryCards';
import RecentTransactionsWidget from '../Components/Dashboard/RecentTransactionsWidget';
import UpcomingWidget from '../Components/Dashboard/UpcomingWidget';
import GoalProgressWidget from '../Components/Dashboard/GoalProgressWidget';
import BudgetAlertWidget from '../Components/Dashboard/BudgetAlertWidget';
import ExpenseCategoryChart from '../Components/Dashboard/ExpenseCategoryChart';
import MonthlyComparisonChart from '../Components/Dashboard/MonthlyComparisonChart';
import NetWorthTrendChart from '../Components/Dashboard/NetWorthTrendChart';
import WeeklyCashflowChart from '../Components/Dashboard/WeeklyCashflowChart';

const { Title } = Typography;

export default function Dashboard() {
    const { props } = usePage();
    const dashboard = props.dashboard || {};
    const currency = props.auth?.user?.currency || 'IDR';

    const {
        income_total = 0,
        expense_total = 0,
        net_worth = 0,
        last_10_transactions = [],
        saving_goals = [],
        budgets = [],
        upcoming_recurring = [],
        category_expenses = [],
        monthly_summary = [],
        net_worth_trend = [],
        weekly_cashflow = [],
    } = dashboard;

    return (
        <App>
            <AuthenticatedLayout title="Dasbor">
                <Head title="Dasbor" />

                <SummaryCards
                    incomeTotal={income_total}
                    expenseTotal={expense_total}
                    netWorth={net_worth}
                    currency={currency}
                />

                {/* Chart Row 1: Expense Category Donut + Monthly Comparison */}
                <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                    <Col xs={24} lg={12}>
                        <ExpenseCategoryChart data={category_expenses} currency={currency} />
                    </Col>
                    <Col xs={24} lg={12}>
                        <MonthlyComparisonChart data={monthly_summary} />
                    </Col>
                </Row>

                {/* Chart Row 2: Net Worth Trend + Weekly Cashflow */}
                <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                    <Col xs={24} lg={12}>
                        <NetWorthTrendChart data={net_worth_trend} />
                    </Col>
                    <Col xs={24} lg={12}>
                        <WeeklyCashflowChart data={weekly_cashflow} />
                    </Col>
                </Row>

                <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                    <Col xs={24} lg={12}>
                        <Card title="Transaksi Terbaru">
                            <RecentTransactionsWidget transactions={last_10_transactions} />
                        </Card>
                    </Col>
                    <Col xs={24} lg={12}>
                        <Card title="Mendatang">
                            <UpcomingWidget upcoming={upcoming_recurring} />
                        </Card>
                    </Col>
                </Row>

                <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                    <Col xs={24} lg={12}>
                        <Card title="Target Tabungan">
                            <GoalProgressWidget goals={saving_goals} />
                        </Card>
                    </Col>
                    <Col xs={24} lg={12}>
                        <Card title="Peringatan Anggaran">
                            <BudgetAlertWidget />
                        </Card>
                    </Col>
                </Row>
            </AuthenticatedLayout>
        </App>
    );
}
