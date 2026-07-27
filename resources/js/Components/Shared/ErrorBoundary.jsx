import { Component } from 'react';
import { Alert, Button } from 'antd';

export default class ErrorBoundary extends Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    handleRetry = () => {
        this.setState({ hasError: false, error: null });
    };

    render() {
        if (this.state.hasError) {
            return (
                <div style={{ padding: 48, maxWidth: 640, margin: '0 auto' }}>
                    <Alert
                        type="error"
                        showIcon
                        message="Something went wrong"
                        description={this.state.error?.message || 'An unexpected error occurred.'}
                        action={
                            <Button size="small" onClick={this.handleRetry}>
                                Try again
                            </Button>
                        }
                    />
                </div>
            );
        }

        return this.props.children;
    }
}
