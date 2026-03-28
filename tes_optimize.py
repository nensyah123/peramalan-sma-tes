import sys
import json
import pandas as pd
from statsmodels.tsa.holtwinters import ExponentialSmoothing
import warnings

warnings.filterwarnings('ignore')

def main():
    if len(sys.argv) > 1:
        with open(sys.argv[1], 'r') as f:
            input_data = f.read()
    else:
        input_data = sys.stdin.read()

    data   = json.loads(input_data)
    values = data['values']

    series = pd.Series(values, dtype=float)

    model = ExponentialSmoothing(
        series,
        trend='add',
        seasonal='add',
        seasonal_periods=12
    )

    fit = model.fit(optimized=True)

    # Hanya kembalikan α, β, γ optimal saja
    alpha = round(max(0.01, min(0.99, float(fit.params['smoothing_level']))),    4)
    beta  = round(max(0.01, min(0.99, float(fit.params['smoothing_trend']))),    4)
    gamma = round(max(0.01, min(0.99, float(fit.params['smoothing_seasonal']))), 4)

    output = {
        'alpha': alpha,
        'beta':  beta,
        'gamma': gamma,
    }

    print(json.dumps(output))

if __name__ == '__main__':
    main()
