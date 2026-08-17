import { Card, Empty, Typography, theme } from 'antd';
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip, Legend } from 'recharts';
import { M3 } from '../../theme/m3Colors';

const { Text } = Typography;

const FALLBACK_COLORS = M3.palette;

function formatRupiah(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
}

function renderCustomLabel({ name, value, percent }) {
  if (percent < 0.05) return null;
  return `${name} (${(percent * 100).toFixed(0)}%)`;
}

export default function ExpenseCategoryChart({ data = [], currency = 'IDR' }) {
  const { token } = theme.useToken();

  if (!data || data.length === 0) {
    return (
      <Card title="Pengeluaran per Kategori">
        <Empty description="Belum ada transaksi bulan ini" />
      </Card>
    );
  }

  const chartData = data.map((item, idx) => ({
    name: item.name,
    value: item.value,
    // Always use distinct palette color by index — guarantees different colors
    fill: FALLBACK_COLORS[idx % FALLBACK_COLORS.length],
  }));

  return (
    <Card title="Pengeluaran per Kategori">
      <ResponsiveContainer width="100%" height={320}>
        <PieChart>
          <Pie
            data={chartData}
            cx="50%"
            cy="50%"
            innerRadius={65}
            outerRadius={110}
            paddingAngle={2}
            dataKey="value"
            nameKey="name"
            label={renderCustomLabel}
            labelLine={{ stroke: token.colorTextSecondary, strokeWidth: 1 }}
            isAnimationActive={true}
          >
            {chartData.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={entry.fill} stroke={token.colorBgContainer} />
            ))}
          </Pie>
          <Tooltip
            formatter={(value) => formatRupiah(value)}
            contentStyle={{
              backgroundColor: token.colorBgElevated,
              borderColor: token.colorBorder,
              borderRadius: 8,
            }}
          />
          <Legend
            layout="horizontal"
            verticalAlign="bottom"
            wrapperStyle={{ color: token.colorText, fontSize: 12 }}
          />
        </PieChart>
      </ResponsiveContainer>
    </Card>
  );
}
