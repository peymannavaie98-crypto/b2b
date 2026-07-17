import React from 'react';
import { createRoot } from 'react-dom/client';

const AdminDashboard = () => {
    return (
        <div className="haminshop-admin-wrapper">
            <h1 className="text-2xl font-bold mb-4">HaminShop B2B - Admin Dashboard</h1>
            <p>Welcome to the multi-role administration area.</p>
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
