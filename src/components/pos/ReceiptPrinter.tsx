import { usePosStore } from '@/store/posStore';

interface ReceiptProps {
  invoiceNo: string;
  isOffline: boolean;
  date: string;
  cart: any[];
  subtotal: number;
  discount: number;
  tax: number;
  total: number;
  tendered: number;
  change: number;
  paymentMethod: string;
}

export function ReceiptPrinter({
  invoiceNo,
  isOffline,
  date,
  cart,
  subtotal,
  discount,
  tax,
  total,
  tendered,
  change,
  paymentMethod
}: ReceiptProps) {
  const initData = usePosStore((state) => state.initData);

  const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: initData?.business?.currency_code || 'USD',
  }).format(val);

  return (
    <div id="receipt-print-area" className="hidden print:block w-[80mm] mx-auto text-black bg-white p-4 font-mono text-[12px] leading-tight">
      {/* Header */}
      <div className="text-center mb-6">
        <h2 className="text-[18px] font-bold uppercase mb-1">{initData?.business?.name || 'TRADEX POS'}</h2>
        <p className="text-[11px] mb-1">Receipt: {invoiceNo}</p>
        <p className="text-[11px]">{new Date(date).toLocaleString()}</p>
        {isOffline && <p className="text-[11px] font-bold mt-1">(OFFLINE MODE)</p>}
      </div>

      {/* Divider */}
      <div className="border-t border-dashed border-black mb-3"></div>

      {/* Items */}
      <div className="mb-4">
        <table className="w-full text-left border-collapse">
          <thead>
            <tr className="border-b border-dashed border-black">
              <th className="py-1 font-normal w-1/2">Item</th>
              <th className="py-1 font-normal text-right">Qty</th>
              <th className="py-1 font-normal text-right">Amt</th>
            </tr>
          </thead>
          <tbody>
            {cart.map((item, idx) => (
              <tr key={idx}>
                <td className="py-1 align-top pr-2">
                  <div>{item.product_name}</div>
                  {item.variation_name && item.variation_name !== 'DUMMY' && item.variation_name !== item.product_name && (
                    <div className="text-[10px] text-gray-600">{item.variation_name}</div>
                  )}
                </td>
                <td className="py-1 align-top text-right">{item.quantity}</td>
                <td className="py-1 align-top text-right">{formatCurrency(item.final_price * item.quantity)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Divider */}
      <div className="border-t border-dashed border-black mb-3"></div>

      {/* Totals */}
      <div className="mb-4 space-y-1">
        <div className="flex justify-between">
          <span>Subtotal:</span>
          <span>{formatCurrency(subtotal)}</span>
        </div>
        {discount > 0 && (
          <div className="flex justify-between">
            <span>Discount:</span>
            <span>-{formatCurrency(discount)}</span>
          </div>
        )}
        {tax > 0 && (
          <div className="flex justify-between">
            <span>Tax:</span>
            <span>{formatCurrency(tax)}</span>
          </div>
        )}
        <div className="flex justify-between font-bold text-[14px] mt-2 pt-2 border-t border-dashed border-black">
          <span>Total:</span>
          <span>{formatCurrency(total)}</span>
        </div>
      </div>

      {/* Payment Info */}
      <div className="mb-6 space-y-1">
        <div className="flex justify-between uppercase">
          <span>{paymentMethod}</span>
          <span>{formatCurrency(tendered)}</span>
        </div>
        {change > 0 && (
          <div className="flex justify-between">
            <span>Change Due:</span>
            <span>{formatCurrency(change)}</span>
          </div>
        )}
      </div>

      {/* Footer */}
      <div className="text-center">
        <p className="mb-1 text-[13px] font-bold">Thank You!</p>
        <p className="text-[10px]">Please come again.</p>
        <div className="mt-8 text-[10px] text-gray-500">Powered by Tradex</div>
      </div>
    </div>
  );
}
