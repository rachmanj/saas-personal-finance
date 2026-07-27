import { useState, useCallback, useRef } from 'react';
import { AutoComplete, Input } from 'antd';
import { apiPost } from '../../utils/api';
import debounce from 'lodash/debounce';

/**
 * @param {{
 *   onSelect?: (merchant: string) => void,
 *   value?: string,
 *   onChange?: (value: string) => void,
 * }} props
 */
export default function MerchantAutocomplete({ onSelect, value, onChange }) {
    const [options, setOptions] = useState([]);
    const [fetching, setFetching] = useState(false);
    const lastFetchId = useRef(0);

    const fetchSuggestions = useCallback(
        debounce(async (query) => {
            if (!query || query.length < 2) {
                setOptions([]);
                return;
            }

            lastFetchId.current += 1;
            const fetchId = lastFetchId.current;

            setFetching(true);

            try {
                const res = await apiPost('/api/transactions/suggestions', { query });
                if (fetchId === lastFetchId.current) {
                    const merchants = (res.data?.merchants || []).map((m) => ({
                        value: m,
                        label: m,
                    }));
                    setOptions(merchants);

                    // If we have a category prediction, we could notify parent
                    if (res.data?.predicted_category_name) {
                        // Future: pass predicted category to parent
                    }
                }
            } catch {
                setOptions([]);
            } finally {
                if (fetchId === lastFetchId.current) {
                    setFetching(false);
                }
            }
        }, 300),
        []
    );

    const handleSearch = (text) => {
        fetchSuggestions(text);
    };

    const handleSelect = (data) => {
        onSelect?.(data);
    };

    return (
        <AutoComplete
            options={options}
            onSearch={handleSearch}
            onSelect={handleSelect}
            value={value}
            onChange={onChange}
            notFoundContent={fetching ? 'Loading...' : null}
        >
            <Input placeholder="Search merchants..." allowClear />
        </AutoComplete>
    );
}
