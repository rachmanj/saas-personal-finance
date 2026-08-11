import { Card, Empty, theme } from 'antd';
import {
  Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis,
} from 'recharts';

function formatRupiah(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
}

export default function WeeklyCashflowChart({ data = [] }) {
  const { token } = theme.useToken();

  if (!data || data.length === 0) {
    return (
      <Card title="Arus Kas Mingguan">
        <Empty description="Belum ada data minggu ini" />
      </Card>
    );
  }

  return (
    <Card title="Arus Kas Mingguan">
      <ResponsiveContainer width="100%" height={280}>
        <BarChart data={data} barCategoryGap="25%">
          <CartesianGrid strokeDasharray="3 3" stroke={token.colorBorderSecondary} />
          <XAxis
            dataKey="week"
            tick={{ fill: token.colorTextSecondary, fontSize: 12 }}
            axisLine={{ stroke: token.colorBorder }}
            tickLine={false}
          />
          <YAxis
            tick={{ fill: token.colorTextSecondary, fontSize: 12 }}
            axisLine={false}
            tickLine={false}
            tickFormatter={(v) => `${(v / 1_000_000).toFixed(0)}M`}
          />
          <Tooltip
            formatter={(value) => formatRupiah(value)}
            contentStyle={{
              backgroundColor: token.colorBgElevated,
              borderColor: token.colorBorder,
              borderRadius: 8,
            }}
          />
          <Legend
            wrapperStyle={{ color: token.colorText, fontSize: 12 }}
          />
          <Bar dataKey="income" name="Masuk" fill="#52c41a" radius={[4, 4, 0, 0]} />
          <Bar dataKey="expense" name="Keluar" fill="#ff4d4f" radius={[4, 4, 0, 0]} />
          <Bar dataKey="net" name="Bersih" fill="#1677ff" radius={[4, 4, 0, 0]} />
        </BarChart>
      </ResponsiveContainer>
    </Card>
  );
}
