import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';

const DashboardContext = React.createContext();

// --- Role: Warehouse ---
const WarehousePanel = () => {
    const [orderId, setOrderId] = useState('');
    const [bijak, setBijak] = useState('');
    const [status, setStatus] = useState('');

    const handleStartPacking = async () => {
        try {
            const apiUrl = window.haminshopData?.api_url || '/wp-json/haminshop/v1';
            const res = await fetch(`${apiUrl}/shipping/${orderId}/packing`, {
                method: 'POST',
                headers: { 'X-WP-Nonce': window.haminshopData?.nonce || '' }
            });
            const data = await res.json();
            setStatus(data.message || 'Error');
        } catch (e) {
            setStatus('Server Error');
        }
    };

    const handleRegisterBijak = async () => {
        try {
            const apiUrl = window.haminshopData?.api_url || '/wp-json/haminshop/v1';
            const res = await fetch(`${apiUrl}/shipping/${orderId}/bijak`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.haminshopData?.nonce || ''
                },
                body: JSON.stringify({ bijak_code: bijak, is_palletized: true, has_insurance: true })
            });
            const data = await res.json();
            setStatus(data.message || 'Error');
        } catch (e) {
            setStatus('Server Error');
        }
    };

    return (
        <div className="p-4 border rounded shadow-sm bg-white">
            <h2 className="text-xl font-bold mb-4 text-orange-600">پنل انباردار</h2>
            <div className="mb-4">
                <input
                    type="text" placeholder="شماره سفارش"
                    className="border p-2 rounded mr-2"
                    value={orderId} onChange={e => setOrderId(e.target.value)}
                />
                <button onClick={handleStartPacking} className="bg-orange-500 text-white px-4 py-2 rounded">
                    شروع بسته‌بندی
                </button>
            </div>
            <div className="mb-4">
                <input
                    type="text" placeholder="کد بیجک باربری"
                    className="border p-2 rounded mr-2"
                    value={bijak} onChange={e => setBijak(e.target.value)}
                />
                <button onClick={handleRegisterBijak} className="bg-green-600 text-white px-4 py-2 rounded">
                    ثبت بیجک و ارسال
                </button>
            </div>
            {status && <p className="text-blue-600 mt-2">{status}</p>}
        </div>
    );
};

// --- Role: Accountant ---
const AccountantPanel = () => {
    const [rmaId, setRmaId] = useState('');
    const [status, setStatus] = useState('');

    const handleApproveRma = async () => {
        try {
            const apiUrl = window.haminshopData?.api_url || '/wp-json/haminshop/v1';
            const res = await fetch(`${apiUrl}/rma/${rmaId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.haminshopData?.nonce || ''
                },
                body: JSON.stringify({ refund_method: 'wallet' })
            });
            const data = await res.json();
            setStatus(data.message || 'Error');
        } catch (e) {
            setStatus('Server Error');
        }
    };

    return (
        <div className="p-4 border rounded shadow-sm bg-white mt-4">
            <h2 className="text-xl font-bold mb-4 text-purple-600">پنل حسابدار</h2>
            <div className="mb-4">
                <h3 className="font-semibold mb-2">تایید مرجوعی (RMA) و عودت به کیف پول</h3>
                <input
                    type="text" placeholder="شماره RMA (سفارش)"
                    className="border p-2 rounded mr-2"
                    value={rmaId} onChange={e => setRmaId(e.target.value)}
                />
                <button onClick={handleApproveRma} className="bg-purple-600 text-white px-4 py-2 rounded">
                    تایید و بازگشت وجه
                </button>
            </div>
            {status && <p className="text-blue-600 mt-2">{status}</p>}
        </div>
    );
};

// --- Main App ---
const AdminDashboard = () => {
    // In a real app, role is fetched from wp_localize_script
    const [role, setRole] = useState('admin');

    return (
        <div className="haminshop-admin-wrapper p-6 bg-gray-50 min-h-screen font-sans" dir="rtl">
            <h1 className="text-3xl font-black mb-6 text-gray-800">HaminShop B2B - داشبورد مدیریت</h1>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <WarehousePanel />
                <AccountantPanel />
            </div>
        </div>
    );
};

document.addEventListener('DOMContentLoaded', () => {
    const rootEl = document.getElementById('haminshop-b2b-admin-root');
    if (rootEl) {
        const root = createRoot(rootEl);
        root.render(<AdminDashboard />);
    }
});
